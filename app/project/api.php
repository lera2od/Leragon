<?php
include "../include/lib.php";

header('Content-Type: application/json');

function checkInput($key)
{
    global $input;
    if (!isset($input["$key"])) {
        throw new Exception('Invalid request ' . $key . ' not provided');
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if ($action === null) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $Docker = new DockerManager();

    switch ($action) {
        case 'containerStart':
            checkInput("containerId");

            $result = $Docker->startContainer($input["containerId"]);
            break;

        case 'containerStop':
            checkInput("containerId");

            $result = $Docker->stopContainer($input["containerId"]);
            break;

        case 'containerRestart':
            checkInput("containerId");

            $result = $Docker->restartContainer($input["containerId"]);
            break;

        case 'containerRemove':
            checkInput('containerId');
            checkInput('force');
            checkInput('volumes');

            $result = $Docker->removeContainer($input['containerId'], $input['force'], $input['volumes']);
            break;

        case 'containerSetDescription':
            checkInput('projectName');
            checkInput('description');

            $description = $input['description'];
            $ProjectData = new ProjectData($input["projectName"]);
            $ProjectData->set('description', $description);

            $result = true;
            break;
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
        
        case 'ContainerLogs':
            checkInput('containerId');
            checkInput('tail');
            checkInput('timestamps');
            
            try {
                $logs = $Docker->getContainerLogs(
                    $input['containerId'],
                    true,
                    true,
                    $input['tail'] === 'all' ? 0 : (int)$input['tail'],
                    $input['timestamps']
                );
                
                echo json_encode([
                    'success' => true,
                    'logs' => $logs
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);    

    $Logger = new Logger("api.txt");
    $Logger->log('API Error: ' . $e->getMessage(), 'ERR' );
}
