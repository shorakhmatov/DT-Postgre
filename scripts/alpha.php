<?php
/**
 * Скрипт Alpha - генерация и добавление заказа в БД
 * С защитой от повторного запуска через Redis
 */

require_once __DIR__ . '/../config/config.php';

// Создаем директорию для логов если не существует
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function generateRandomOrder($pdo) {
    // Получаем случайный продукт
    $stmt = $pdo->prepare("SELECT id, price FROM products WHERE is_active = 1 ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception("No active products found");
    }
    
    // Генерируем случайные данные заказа
    $quantity = rand(1, 5);
    $unitPrice = $product['price'];
    $totalPrice = $unitPrice * $quantity;
    
    $customerNames = ['Иван Петров', 'Мария Сидорова', 'Алексей Козлов', 'Елена Волкова', 'Дмитрий Орлов'];
    $customerName = $customerNames[array_rand($customerNames)];
    $customerEmail = strtolower(str_replace(' ', '.', $customerName)) . rand(1, 999) . '@example.com';
    
    // Вставляем заказ
    $stmt = $pdo->prepare("
        INSERT INTO orders (product_id, quantity, unit_price, total_price, customer_name, customer_email, purchase_time) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $product['id'],
        $quantity,
        $unitPrice,
        $totalPrice,
        $customerName,
        $customerEmail
    ]);
    
    return [
        'order_id' => $pdo->lastInsertId(),
        'product_id' => $product['id'],
        'quantity' => $quantity,
        'total_price' => $totalPrice,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail
    ];
}

try {
    logMessage("Alpha script started", "INFO");
    
    $lockHandle = null;
    $useRedis = false;
    
    // Пытаемся использовать Redis для блокировки
    try {
        $redis = getRedisConnection();
        $lockKey = ALPHA_LOCK_KEY;
        $lockValue = uniqid(gethostname() . '_', true);
        
        // Пытаемся установить блокировку через Redis
        $lockAcquired = $redis->set($lockKey, $lockValue, ['NX', 'EX' => ALPHA_LOCK_TIMEOUT]);
        
        if (!$lockAcquired) {
            $message = "Alpha script is already running (Redis lock). Skipping execution.";
            logMessage($message, "WARNING");
            echo json_encode(['status' => 'skipped', 'message' => $message]);
            exit;
        }
        
        $useRedis = true;
        logMessage("Redis lock acquired: $lockValue", "INFO");
        
    } catch (Exception $e) {
        // Fallback к файловой блокировке
        logMessage("Redis unavailable, using file lock: " . $e->getMessage(), "WARNING");
        
        $lockHandle = acquireFileLock(ALPHA_LOCK_KEY, ALPHA_LOCK_TIMEOUT);
        
        if (!$lockHandle) {
            $message = "Alpha script is already running (file lock). Skipping execution.";
            logMessage($message, "WARNING");
            echo json_encode(['status' => 'skipped', 'message' => $message]);
            exit;
        }
        
        logMessage("File lock acquired", "INFO");
    }
    
    // Подключение к базе данных
    $pdo = getDbConnection();
    
    // Добавляем паузу для демонстрации эффекта блокировки
    sleep(1);
    
    // Генерируем и добавляем заказ
    $orderData = generateRandomOrder($pdo);
    
    logMessage("Order created: ID " . $orderData['order_id'], "INFO");
    
    // Освобождаем блокировку
    if ($useRedis && isset($redis) && isset($lockKey) && isset($lockValue)) {
        $script = "
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('del', KEYS[1])
            else
                return 0
            end
        ";
        $redis->eval($script, [$lockKey, $lockValue], 1);
        logMessage("Redis lock released: $lockValue", "INFO");
    } elseif ($lockHandle) {
        releaseFileLock($lockHandle, ALPHA_LOCK_KEY);
        logMessage("File lock released", "INFO");
    }
    
    // Возвращаем результат
    $result = [
        'status' => 'success',
        'message' => 'Order created successfully',
        'data' => $orderData,
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
    ];
    
    echo json_encode($result);
    logMessage("Alpha script completed successfully", "INFO");
    
} catch (Exception $e) {
    // В случае ошибки освобождаем блокировку
    if (isset($useRedis) && $useRedis && isset($redis) && isset($lockKey) && isset($lockValue)) {
        $script = "
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('del', KEYS[1])
            else
                return 0
            end
        ";
        $redis->eval($script, [$lockKey, $lockValue], 1);
    } elseif (isset($lockHandle) && $lockHandle) {
        releaseFileLock($lockHandle, ALPHA_LOCK_KEY);
    }
    
    $errorMessage = "Alpha script error: " . $e->getMessage();
    logMessage($errorMessage, "ERROR");
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(500);
}
?>
