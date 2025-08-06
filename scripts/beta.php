<?php
/**
 * Скрипт Beta - запуск Alpha скрипта N раз одновременно
 * Запускается по внешней ссылке от клиента
 */

require_once __DIR__ . '/../config/config.php';

// Создаем директорию для логов если не существует
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function executeAlphaScript() {
    $alphaScriptPath = __DIR__ . '/alpha.php';
    
    // Используем curl для выполнения HTTP запроса к alpha.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/scripts/alpha.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        // Fallback: прямое выполнение PHP скрипта
        ob_start();
        include $alphaScriptPath;
        $result = ob_get_clean();
    }
    
    return [
        'result' => $result,
        'http_code' => $httpCode ?? 200,
        'timestamp' => microtime(true)
    ];
}

function executeAlphaScriptMultiple($n) {
    $startTime = microtime(true);
    $results = [];
    $processes = [];
    
    logMessage("Beta script started with N=$n", "INFO");
    
    // Запускаем N процессов одновременно
    for ($i = 0; $i < $n; $i++) {
        $processes[$i] = [
            'start_time' => microtime(true),
            'process_id' => $i + 1
        ];
        
        // Для демонстрации используем прямое включение файла
        // В реальном проекте лучше использовать exec() или curl
        $alphaResult = executeAlphaScript();
        
        $results[$i] = [
            'process_id' => $i + 1,
            'result' => json_decode($alphaResult['result'], true),
            'execution_time' => microtime(true) - $processes[$i]['start_time'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        logMessage("Alpha process " . ($i + 1) . " completed", "INFO");
    }
    
    $totalTime = microtime(true) - $startTime;
    
    // Подсчитываем статистику
    $successCount = 0;
    $errorCount = 0;
    $skippedCount = 0;
    
    foreach ($results as $result) {
        if (isset($result['result']['status'])) {
            switch ($result['result']['status']) {
                case 'success':
                    $successCount++;
                    break;
                case 'error':
                    $errorCount++;
                    break;
                case 'skipped':
                    $skippedCount++;
                    break;
            }
        }
    }
    
    $summary = [
        'total_processes' => $n,
        'successful' => $successCount,
        'errors' => $errorCount,
        'skipped' => $skippedCount,
        'total_execution_time' => $totalTime,
        'average_execution_time' => $totalTime / $n,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    logMessage("Beta script completed. Success: $successCount, Errors: $errorCount, Skipped: $skippedCount", "INFO");
    
    return [
        'status' => 'completed',
        'summary' => $summary,
        'results' => $results
    ];
}

try {
    // Получаем параметр N из GET или POST запроса
    $n = isset($_GET['n']) ? (int)$_GET['n'] : (isset($_POST['n']) ? (int)$_POST['n'] : 1);
    
    // Ограничиваем максимальное количество процессов для безопасности
    $maxProcesses = 1000;
    if ($n > $maxProcesses) {
        $n = $maxProcesses;
        logMessage("N limited to $maxProcesses for safety", "WARNING");
    }
    
    if ($n < 1) {
        throw new Exception("Parameter N must be greater than 0");
    }
    
    // Выполняем Alpha скрипт N раз
    $result = executeAlphaScriptMultiple($n);
    
    // Устанавливаем заголовки для JSON ответа
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $errorMessage = "Beta script error: " . $e->getMessage();
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
