<?php
session_start();
include $_SERVER["DOCUMENT_ROOT"] . "/include/mysql.php";
include "../include/lib.php";

$isApiKeyAuth = isset($_SERVER['HTTP_X_API_KEY']) || isset($_GET['apikey']);
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['apikey'] ?? null;

$isAuthed = false;
if ($isApiKeyAuth) {
    $isAuthed = apiKeyValidate($apiKey, true);
}

session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); 

if (ob_get_level()) {
    ob_end_clean();
}

function sendEvent($data, $event = 'message')
{
    echo "event: {$event}\n";
    echo "data: " . str_replace(["\r\n", "\n", "\r"], "\\n", $data) . "\n\n";
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

function sendError($message)
{
    sendEvent($message, 'error');
}

if(!$isAuthed) {
    sendError("API Key is invalid");
    exit;
}

if (!isset($_GET['action'])) {
    sendError("No action specified");
    exit;
}

$action = $_GET['action'];

if ($action === 'logs') {
    if (!isset($_GET['container'])) {
        sendError("No container specified");
        exit;
    }

    $containerId = $_GET['container'];
    $timestamps = isset($_GET['timestamps']) && $_GET['timestamps'] === 'true';
    $tail = $_GET['tail'] ?? '100';

    try {
        set_time_limit(0);
        ignore_user_abort(false);

        $Docker = new DockerManager();
        
        $apiVersion = 'v1.41';
        $query = http_build_query([
            'stdout' => 1,
            'stderr' => 1,
            'follow' => 1,
            'timestamps' => $timestamps ? 1 : 0,
            'tail' => $tail === 'all' ? 'all' : (int) $tail
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost/{$apiVersion}/containers/{$containerId}/logs?{$query}",
            CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock',
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 0, // No timeout for streaming
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                static $buffer = '';
                
                if (connection_aborted()) {
                    return 0;
                }
                
                $buffer .= $data;
                
                while (strlen($buffer) >= 8) {
                    $header = unpack('C1type/C3null/N1size', substr($buffer, 0, 8));
                    
                    if ($header === false || $header['size'] < 0) {
                        $buffer = substr($buffer, 1);
                        continue;
                    }
                    
                    $messageSize = $header['size'];
                    
                    if (strlen($buffer) < 8 + $messageSize) {
                        break; 
                    }
                    
                    $message = substr($buffer, 8, $messageSize);
                    $buffer = substr($buffer, 8 + $messageSize);
                    
                    if ($message !== false && $message !== '') {
                        sendEvent(rtrim($message, "\r\n"));
                    }
                }
                
                return strlen($data);
            }
        ]);

        sendEvent("Connected to container logs", 'connected');

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);

        if ($errno !== 0) {
            sendError("cURL error ({$errno}): {$error}");
        } elseif ($result === false) {
            sendError("Failed to connect to Docker daemon");
        }

    } catch (Exception $e) {
        sendError("Exception: " . $e->getMessage());
    }
    
    sendEvent("Disconnected from container logs", 'disconnected');
}