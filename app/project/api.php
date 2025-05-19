<?php

$containerId = $_GET['containerId'] ?? null;
$action = $_GET['action'] ?? null;

if ($containerId === null || $action === null) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

include "../docker.php";

switch ($action) {
    case 'start':
        $Docker = new DockerManager();
        $result = $Docker->startContainer($containerId);
        break;
    case 'stop':
        $Docker = new DockerManager();
        $result = $Docker->stopContainer($containerId);
        break;
    case 'restart':
        $Docker = new DockerManager();
        $result = $Docker->restartContainer($containerId);
        break;
    case 'setDescription':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['description'])) {
            echo json_encode(['error' => 'Description not provided']);
            exit;
        }
        $description = $input['description'];
        $ProjectData = new ProjectData($containerId);
        $ProjectData->set('description', $description);
        $result = true;
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to perform action']);
}