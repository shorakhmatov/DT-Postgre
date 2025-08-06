<?php
/**
 * Скрипт для тестирования системы управления заказами
 * Проверяет подключение к БД, Redis и работоспособность всех компонентов
 */

require_once __DIR__ . '/config/config.php';

echo "🧪 Тестирование системы управления заказами\n";
echo "==========================================\n\n";

$errors = [];
$warnings = [];

// Тест 1: Подключение к базе данных
echo "1. Тестирование подключения к MySQL...\n";
try {
    $pdo = getDbConnection();
    echo "   ✅ Подключение к MySQL успешно\n";
    
    // Проверяем существование таблиц
    $tables = ['categories', 'products', 'orders', 'statistics'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Таблица '$table' существует\n";
        } else {
            $errors[] = "Таблица '$table' не найдена";
            echo "   ❌ Таблица '$table' не найдена\n";
        }
    }
    
    // Проверяем количество записей
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    echo "   📊 Продуктов в БД: $productCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetch()['count'];
    echo "   📊 Заказов в БД: $orderCount\n";
    
} catch (Exception $e) {
    $errors[] = "Ошибка подключения к MySQL: " . $e->getMessage();
    echo "   ❌ Ошибка подключения к MySQL: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 2: Подключение к Redis
echo "2. Тестирование подключения к Redis...\n";
try {
    $redis = getRedisConnection();
    $redis->ping();
    echo "   ✅ Подключение к Redis успешно\n";
    
    // Тестируем операции с Redis
    $testKey = 'test_key_' . time();
    $redis->set($testKey, 'test_value', 10);
    $value = $redis->get($testKey);
    if ($value === 'test_value') {
        echo "   ✅ Операции с Redis работают\n";
        $redis->del($testKey);
    } else {
        $warnings[] = "Проблемы с операциями Redis";
        echo "   ⚠️ Проблемы с операциями Redis\n";
    }
    
} catch (Exception $e) {
    $warnings[] = "Redis недоступен: " . $e->getMessage();
    echo "   ⚠️ Redis недоступен: " . $e->getMessage() . "\n";
    echo "   ℹ️ Система будет работать без кеширования\n";
}

echo "\n";

// Тест 3: Проверка PHP скриптов
echo "3. Тестирование PHP скриптов...\n";

$scripts = [
    'alpha.php' => 'scripts/alpha.php',
    'beta.php' => 'scripts/beta.php',
    'gamma.php' => 'scripts/gamma.php'
];

foreach ($scripts as $name => $path) {
    if (file_exists($path)) {
        echo "   ✅ Скрипт $name найден\n";
        
        // Проверяем синтаксис
        $output = [];
        $return_var = 0;
        exec("php -l $path 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "   ✅ Синтаксис $name корректен\n";
        } else {
            $errors[] = "Синтаксическая ошибка в $name";
            echo "   ❌ Синтаксическая ошибка в $name\n";
        }
    } else {
        $errors[] = "Скрипт $name не найден";
        echo "   ❌ Скрипт $name не найден\n";
    }
}

echo "\n";

// Тест 4: Проверка веб-клиента
echo "4. Тестирование веб-клиента...\n";
if (file_exists('client/index.html')) {
    echo "   ✅ Веб-клиент найден\n";
    
    $content = file_get_contents('client/index.html');
    if (strpos($content, 'scripts/beta.php') !== false) {
        echo "   ✅ Ссылки на API корректны\n";
    } else {
        $warnings[] = "Возможные проблемы со ссылками в клиенте";
        echo "   ⚠️ Возможные проблемы со ссылками в клиенте\n";
    }
} else {
    $errors[] = "Веб-клиент не найден";
    echo "   ❌ Веб-клиент не найден\n";
}

echo "\n";

// Тест 5: Проверка директорий и прав
echo "5. Проверка файловой системы...\n";

$directories = ['config', 'database', 'scripts', 'client'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "   ✅ Директория '$dir' существует\n";
    } else {
        $errors[] = "Директория '$dir' не найдена";
        echo "   ❌ Директория '$dir' не найдена\n";
    }
}

// Создаем директорию для логов если не существует
if (!is_dir('logs')) {
    if (mkdir('logs', 0755, true)) {
        echo "   ✅ Директория 'logs' создана\n";
    } else {
        $warnings[] = "Не удалось создать директорию 'logs'";
        echo "   ⚠️ Не удалось создать директорию 'logs'\n";
    }
} else {
    echo "   ✅ Директория 'logs' существует\n";
}

echo "\n";

// Итоговый отчет
echo "📋 ИТОГОВЫЙ ОТЧЕТ\n";
echo "================\n";

if (empty($errors)) {
    echo "🎉 Все критические тесты пройдены успешно!\n";
} else {
    echo "❌ Обнаружены критические ошибки:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ Предупреждения:\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
}

echo "\n🚀 Рекомендации для запуска:\n";
echo "1. Выполните SQL скрипты из директории database/\n";
echo "2. Запустите веб-сервер: php -S localhost:8000 -t .\n";
echo "3. Откройте http://localhost:8000/client/ в браузере\n";
echo "4. Установите Redis для полной функциональности\n";

echo "\n✨ Система готова к использованию!\n";
?>
