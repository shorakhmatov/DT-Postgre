<?php
/**
 * Скрипт Gamma - генерация статистики по последним 100 заказам
 * Оптимизирован для максимальной производительности
 */

require_once __DIR__ . '/../config/config.php';

// Создаем директорию для логов если не существует
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function getOptimizedStatistics($pdo) {
    $startTime = microtime(true);
    
    // Оптимизированный запрос для получения статистики по последним 100 заказам
    // Используем одним запросом для максимальной производительности
    $sql = "
        SELECT 
            c.name as category_name,
            p.name as product_name,
            COUNT(*) as order_count,
            SUM(o.quantity) as total_quantity,
            SUM(o.total_price) as total_revenue,
            MIN(o.purchase_time) as first_order_time,
            MAX(o.purchase_time) as last_order_time,
            AVG(o.total_price) as avg_order_value
        FROM (
            SELECT * FROM orders 
            ORDER BY purchase_time DESC 
            LIMIT 100
        ) o
        INNER JOIN products p ON o.product_id = p.id
        INNER JOIN categories c ON p.category_id = c.id
        GROUP BY c.id, c.name, p.id, p.name
        ORDER BY total_quantity DESC, total_revenue DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $productStats = $stmt->fetchAll();
    
    // Получаем общую статистику по категориям
    $categorySql = "
        SELECT 
            c.name as category_name,
            COUNT(*) as order_count,
            SUM(o.quantity) as total_quantity,
            SUM(o.total_price) as total_revenue,
            AVG(o.total_price) as avg_order_value,
            COUNT(DISTINCT o.product_id) as unique_products
        FROM (
            SELECT * FROM orders 
            ORDER BY purchase_time DESC 
            LIMIT 100
        ) o
        INNER JOIN products p ON o.product_id = p.id
        INNER JOIN categories c ON p.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY total_quantity DESC
    ";
    
    $stmt = $pdo->prepare($categorySql);
    $stmt->execute();
    $categoryStats = $stmt->fetchAll();
    
    // Получаем временные рамки
    $timeSql = "
        SELECT 
            MIN(purchase_time) as first_order,
            MAX(purchase_time) as last_order,
            COUNT(*) as total_orders,
            SUM(total_price) as total_revenue,
            AVG(total_price) as avg_order_value
        FROM (
            SELECT * FROM orders 
            ORDER BY purchase_time DESC 
            LIMIT 100
        ) o
    ";
    
    $stmt = $pdo->prepare($timeSql);
    $stmt->execute();
    $timeStats = $stmt->fetch();
    
    // Вычисляем разницу во времени
    $timeDiff = null;
    $timeDiffSeconds = null;
    if ($timeStats['first_order'] && $timeStats['last_order']) {
        $firstTime = new DateTime($timeStats['first_order']);
        $lastTime = new DateTime($timeStats['last_order']);
        $interval = $firstTime->diff($lastTime);
        
        $timeDiffSeconds = $lastTime->getTimestamp() - $firstTime->getTimestamp();
        $timeDiff = [
            'days' => $interval->days,
            'hours' => $interval->h,
            'minutes' => $interval->i,
            'seconds' => $interval->s,
            'total_seconds' => $timeDiffSeconds,
            'formatted' => $interval->format('%d дней, %h часов, %i минут, %s секунд')
        ];
    }
    
    $executionTime = microtime(true) - $startTime;
    
    return [
        'summary' => [
            'total_orders' => (int)$timeStats['total_orders'],
            'total_revenue' => (float)$timeStats['total_revenue'],
            'avg_order_value' => (float)$timeStats['avg_order_value'],
            'first_order_time' => $timeStats['first_order'],
            'last_order_time' => $timeStats['last_order'],
            'time_period' => $timeDiff,
            'execution_time_ms' => round($executionTime * 1000, 2)
        ],
        'categories' => $categoryStats,
        'products' => $productStats,
        'timestamp' => date('Y-m-d H:i:s'),
        'query_performance' => [
            'execution_time_seconds' => $executionTime,
            'queries_executed' => 3,
            'records_analyzed' => 100
        ]
    ];
}

function getCachedStatistics($pdo, $redis) {
    $cacheKey = 'gamma_stats_last_100';
    $cacheTimeout = 5; // Кешируем на 5 секунд для высокой производительности
    
    try {
        // Пытаемся получить из кеша
        $cachedData = $redis->get($cacheKey);
        if ($cachedData) {
            $data = json_decode($cachedData, true);
            $data['from_cache'] = true;
            $data['cache_age_seconds'] = time() - $data['cache_timestamp'];
            return $data;
        }
    } catch (Exception $e) {
        logMessage("Redis cache error: " . $e->getMessage(), "WARNING");
    }
    
    // Если нет в кеше, получаем свежие данные
    $stats = getOptimizedStatistics($pdo);
    $stats['from_cache'] = false;
    $stats['cache_timestamp'] = time();
    
    try {
        // Сохраняем в кеш
        $redis->setex($cacheKey, $cacheTimeout, json_encode($stats));
    } catch (Exception $e) {
        logMessage("Redis cache save error: " . $e->getMessage(), "WARNING");
    }
    
    return $stats;
}

try {
    logMessage("Gamma script started", "INFO");
    
    // Подключение к базе данных
    $pdo = getDbConnection();
    
    // Подключение к Redis для кеширования
    $redis = null;
    try {
        $redis = getRedisConnection();
    } catch (Exception $e) {
        logMessage("Redis not available, using direct DB queries: " . $e->getMessage(), "WARNING");
    }
    
    // Получаем статистику (с кешированием если доступен Redis)
    if ($redis) {
        $statistics = getCachedStatistics($pdo, $redis);
    } else {
        $statistics = getOptimizedStatistics($pdo);
        $statistics['from_cache'] = false;
    }
    
    logMessage("Gamma script completed in " . $statistics['summary']['execution_time_ms'] . "ms", "INFO");
    
    // Устанавливаем заголовки для JSON ответа
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($statistics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $errorMessage = "Gamma script error: " . $e->getMessage();
    logMessage($errorMessage, "ERROR");
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
