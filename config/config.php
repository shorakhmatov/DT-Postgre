<?php
/**
 * Конфигурация базы данных и Redis
 */

// Настройки базы данных MySQL
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_39636885_dt_postgre');
define('DB_USER', 'if0_XXXXXX');
define('DB_PASS', '1234XXXXXXXXX');
define('DB_CHARSET', 'utf8mb4');

// Настройки Redis (для локального использования)
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_TIMEOUT', 30);

// Общие настройки
define('ALPHA_LOCK_KEY', 'alpha_script_lock');
define('ALPHA_LOCK_TIMEOUT', 60); // секунды

/**
 * Получение подключения к базе данных
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

/**
 * Получение подключения к Redis
 */
function getRedisConnection() {
    try {
        if (!class_exists('Redis')) {
            throw new Exception("Redis extension not installed");
        }
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        return $redis;
    } catch (Exception $e) {
        error_log("Redis connection failed: " . $e->getMessage());
        throw new Exception("Redis connection failed: " . $e->getMessage());
    }
}

/**
 * Альтернативная блокировка через файловую систему (fallback для Redis)
 */
function acquireFileLock($lockName, $timeout = 60) {
    $lockDir = __DIR__ . '/../locks';
    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0755, true);
    }
    
    $lockFile = $lockDir . '/' . $lockName . '.lock';
    $lockHandle = fopen($lockFile, 'w');
    
    if (!$lockHandle) {
        return false;
    }
    
    if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
        // Записываем время создания блокировки
        fwrite($lockHandle, time());
        return $lockHandle;
    } else {
        fclose($lockHandle);
        return false;
    }
}

/**
 * Освобождение файловой блокировки
 */
function releaseFileLock($lockHandle, $lockName) {
    if ($lockHandle) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        $lockDir = __DIR__ . '/../locks';
        $lockFile = $lockDir . '/' . $lockName . '.lock';
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
        return true;
    }
    return false;
}

/**
 * Логирование
 */
function logMessage($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logDir . '/app.log', $logMessage, FILE_APPEND | LOCK_EX);
}
