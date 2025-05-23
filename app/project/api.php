<?php

include $_SERVER["DOCUMENT_ROOT"] . "/include/mysql.php";
include "../include/lib.php";


$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;

$onlyApiKeyActions = [
    "ContainerLogs"
];


header('Content-Type: application/json');
header('Cache-Control: no-cache');

$isApiKeyAuth = isset($_SERVER['HTTP_X_API_KEY']) || isset($_GET['apikey']);
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['apikey'] ?? null;

if ($isApiKeyAuth) {
    apiKeyValidate($apiKey);
} else {
    if (in_array($action, $onlyApiKeyActions)) {
        http_response_code(403);
        echo json_encode(["error" => "API key required for this action"]);
        exit;
    }
    apiLoginCheck();
}

try {
    $Docker = new DockerManager();

    if ($isApiKeyAuth && isset($_GET['action']) && $_GET['action'] === 'logs') {
        $containerId = $_GET['container'] ?? null;
        $tail = $_GET['tail'] ?? '100';
        $timestamps = $_GET['timestamps'] ?? 'false';

        if (!$containerId) {
            throw new Exception('Container ID required');
        }

        set_time_limit(30);
        ini_set('memory_limit', '128M');

        $logs = $Docker->getContainerLogs(
            $containerId,
            true,
            true,
            $tail === 'all' ? 0 : (int) $tail,
            $timestamps === 'true'
        );

        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        exit;
    }

    if ($action === null) {
        throw new Exception('Invalid request - action not specified');
    }

    function checkInput($key)
    {
        global $input;
        if (!isset($input[$key])) {
            throw new Exception('Invalid request: ' . $key . ' not provided');
        }
    }

    switch ($action) {
        case 'ContainerStart':
            checkInput("containerId");

            $result = $Docker->startContainer($input["containerId"]);
            break;

        case 'ContainerStop':
            checkInput("containerId");

            $result = $Docker->stopContainer($input["containerId"]);
            break;

        case 'ContainerRestart':
            checkInput("containerId");

            $result = $Docker->restartContainer($input["containerId"]);
            break;

        case 'ContainerRemove':
            checkInput('containerId');
            checkInput('force');
            checkInput('volumes');

            $result = $Docker->removeContainer($input['containerId'], $input['force'], $input['volumes']);
            break;

        case 'ContainerSetDescription':
            checkInput('projectName');
            checkInput('description');

            $description = $input['description'];
            $ProjectData = new ProjectData($input["projectName"]);
            $ProjectData->set('description', $description);

            $result = true;
            break;

        case 'ContainerInspect':
            checkInput('containerId');

            $details = $Docker->inspectContainer($input['containerId']);
            echo json_encode([
                'success' => true,
                'details' => $details
            ]);
            exit;

        case 'ContainerLogs':
            checkInput('containerId');
            checkInput('tail');
            checkInput('timestamps');

            set_time_limit(120);
            ini_set('memory_limit', '256M');

            $Docker = new DockerManager();
            $logs = $Docker->getContainerLogs(
                $input['containerId'],
                true,
                true,
                $input['tail'] === 'all' ? 1000 : (int) $input['tail'],
                $input['timestamps']
            );

            header('Content-Type: application/json');
            header('Connection: close');

            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);

            exit;

        case 'ImageRemove':
            checkInput('imageId');

            $Docker->removeImage($input['imageId']);

            $result = true;
            break;

        case 'NetworkRemove':
            checkInput('networkId');

            $result = $Docker->removeNetwork($input['networkId']);
            break;

        case 'NetworkInspect':
            checkInput('networkId');

            $details = $Docker->inspectNetwork($input['networkId']);
            echo json_encode([
                'success' => true,
                'details' => $details
            ]);
            exit;

        case 'VolumeRemove':
            checkInput('volumeName');
            checkInput('force');

            $result = $Docker->removeVolume($input['volumeName'], $input['force']);
            break;

        case 'VolumeInspect':
            checkInput('volumeName');

            $details = $Docker->inspectVolume($input['volumeName']);
            echo json_encode([
                'success' => true,
                'details' => $details
            ]);
            exit;

        default:
            throw new Exception('Invalid action');
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Action completed successfully'
        ]);
    } else {
        throw new Exception('Action failed');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);

    $Logger = new Logger("api.txt");
    $Logger->log('API Error: ' . $e->getMessage(), 'ERR');
}