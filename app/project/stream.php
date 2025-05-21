<?php
include "../include/lib.php";

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

function sendEvent($data)
{
    echo "data: " . $data . "\n\n";
    ob_flush();
    flush();
}

if (!isset($_GET['action'])) {
    sendEvent("error: No action specified");
    exit;
}

$action = $_GET['action'];

if ($action === 'logs') {
    if (!isset($_GET['container'])) {
        sendEvent("error: No container specified");
        exit;
    }

    $containerId = $_GET['container'];
    $timestamps = isset($_GET['timestamps']) && $_GET['timestamps'] === 'true';
    $tail = $_GET['tail'] ?? '100';

    try {
        $Docker = new DockerManager();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost/containers/{$containerId}/logs?" . http_build_query([
                'stdout' => 1,
                'stderr' => 1,
                'follow' => 1,
                'timestamps' => $timestamps ? 1 : 0,
                'tail' => $tail === 'all' ? 'all' : (int) $tail
            ]),
            CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock',
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                $header = unpack('Ctype/C3null/Nsize', substr($data, 0, 8));

                if ($header !== false) {
                    $message = substr($data, 8, $header['size']);
                    if ($message !== false) {
                        sendEvent($message);
                    }
                }

                return strlen($data);
            }
        ]);

        curl_exec($ch);

        if (curl_errno($ch)) {
            sendEvent("error: " . curl_error($ch));
        }

        curl_close($ch);
    } catch (Exception $e) {
        sendEvent("error: " . $e->getMessage());
    }
}