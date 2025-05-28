<?php
require 'include/lib.php';

if (!isset($_SESSION["user"])) {
    header("Location: /login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($_GET["action"]) {
        case 'create_api_key':
            $description = $data['description'] ?? '';
            $apiKey = bin2hex(random_bytes(32));

            $stmt = $conn->prepare("INSERT INTO api_keys (key_value, description, user_id) SELECT ?, ?, id FROM users WHERE username = ?");
            $stmt->bind_param("sss", $apiKey, $description, $_SESSION['user']);

            if (!$stmt->execute()) {
                echo json_encode(['error' => 'Failed to create API key']);
                exit;
            }
            echo json_encode(['success' => true]);
            exit;

        case 'toggle_api_key':
            $id = $data['id'] ?? 0;
            $active = $data['active'] ? 1 : 0;

            $stmt = $conn->prepare("UPDATE api_keys SET active = ? WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)");
            $stmt->bind_param("iis", $active, $id, $_SESSION['user']);

            if (!$stmt->execute()) {
                echo json_encode(['error' => 'Failed to update API key']);
                exit;
            }
            echo json_encode(['success' => true]);
            exit;

        case 'delete_api_key':
            $id = $data['id'] ?? 0;

            $stmt = $conn->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)");
            $stmt->bind_param("is", $id, $_SESSION['user']);

            if (!$stmt->execute()) {
                echo json_encode(['error' => 'Failed to delete API key']);
                exit;
            }
            echo json_encode(['success' => true]);
            exit;

        case 'regenerate_user_api_key':
            $newApiKey = bin2hex(random_bytes(32));

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("UPDATE users SET api_key = ? WHERE username = ?");
                $stmt->bind_param("ss", $newApiKey, $_SESSION['user']);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update user API key');
                }

                $stmt = $conn->prepare("SELECT id, api_key, username FROM users WHERE username = ?");
                $stmt->bind_param("s", $_SESSION['user']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                if (!$user) {
                    throw new Exception('User not found');
                }
                $userId = $user['id'];
                $oldApiKey = $user['api_key'];
                $description = $user['username'] . "'s API Key";

                if (!empty($oldApiKey)) {
                    $stmt = $conn->prepare("DELETE FROM api_keys WHERE key_value = ? AND user_id = ?");
                    $stmt->bind_param("si", $oldApiKey, $userId);
                    $stmt->execute();
                }

                $active = 1;
                $stmt = $conn->prepare("INSERT INTO api_keys (key_value, description, user_id, active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $newApiKey, $description, $userId, $active);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert new API key');
                }

                $conn->commit();
                echo json_encode(['success' => true, 'api_key' => $newApiKey]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;

    }
}


$stmt = $conn->prepare("SELECT * FROM api_keys WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->bind_param("s", $_SESSION["user"]);
$stmt->execute();
$apiKeys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT api_key FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION["user"]);
$stmt->execute();
$userApiKey = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - Docker Manager - API Keys</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include "include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>

        <div class="tab-content containers-tab">
            <div class="project-overview">
                <div class="project-header">
                    <div class="project-title-group">
                        <h3 class="project-title">API Keys</h3>
                    </div>
                    <button class="btn btn-primary" onclick="createApiKey()">
                        <i class="fas fa-plus"></i> Create API Key
                    </button>
                </div>

                <div class="container-list" style="margin-top: 20px;">

                    <div class="container-card">
                        <div class="container-details">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                    <div class="container-icon"
                                        style="background-image: <?= gradientFromText($userApiKey['api_key']) ?>">
                                        <i class="fas fa-key"></i>
                                    </div>
                                    <div class="container-name-wrapper">
                                        <h4 class="container-name">Your User API Key</h4>
                                        <div class="container-image">
                                            <span>This is your main user API key.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="container-badge running">User Key</div>
                            </div>
                            <div class="container-info">
                                <div class="info-item">
                                    <i class="fas fa-key"></i>
                                    <span><?= substr($userApiKey['api_key'], 0, 16) ?>...</span>
                                </div>
                                <div class="info-item" onclick="explain()" style="cursor: pointer;">
                                    <i class="fas fa-message"></i>
                                    <span>This is your API Key. Some parts...</span>
                                </div>
                            </div>
                        </div>
                        <div class="container-actions">
                            <button class="btn btn-secondary btn-sm"
                                onclick="viewApiKey('<?= $userApiKey['api_key'] ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="regenerateUserApiKey()">
                                <i class="fas fa-sync"></i> Change API Key
                            </button>
                        </div>
                    </div>

                    <?php foreach ($apiKeys as $key): ?>
                        <div class="container-card">
                            <div class="container-details">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                        <div class="container-icon"
                                            style="background-image: <?= gradientFromText($key['key_value']) ?>">
                                            <i class="fas fa-key"></i>
                                        </div>
                                        <div class="container-name-wrapper">
                                            <h4 class="container-name">
                                                <?= htmlspecialchars($key['description'] ?? 'API Key') ?>
                                            </h4>
                                            <div class="container-image">
                                                Created: <?= date('Y-m-d H:i', strtotime($key['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="container-badge <?= $key['active'] ? 'running' : 'stopped' ?>">
                                        <?= $key['active'] ? 'Active' : 'Inactive' ?>
                                    </div>
                                </div>
                                <div class="container-info">
                                    <div class="info-item">
                                        <i class="fas fa-key"></i>
                                        <span><?= substr($key['key_value'], 0, 16) ?>...</span>
                                    </div>
                                    <?php if ($key['last_used']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Last used: <?= date('Y-m-d H:i', strtotime($key['last_used'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="container-actions">
                                <button class="btn btn-secondary btn-sm" onclick="viewApiKey('<?= $key['key_value'] ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-secondary btn-sm"
                                    onclick="toggleApiKey(<?= $key['id'] ?>, <?= $key['active'] ?>)">
                                    <i class="fas fa-<?= $key['active'] ? 'pause' : 'play' ?>"></i>
                                    <?= $key['active'] ? 'Disable' : 'Enable' ?>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteApiKey(<?= $key['id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        async function createApiKey() {
            const description = await promptModal('API Key Description', '', 'Enter a description for this API key');
            if (!description) return;

            lockUser();

            const response = await fetch('?action=create_api_key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ description })
            });

            const data = await response.json();
            if (data.error) {
                toast.show(data.error, 'error');
                return;
            }

            toast.show('API key created successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }

        function viewApiKey(key) {
            modal.show({
                title: 'API Key',
                icon: 'key',
                content: `<div class="input">
                <input type="text" value="${key}" id="apiKeyInput" readonly />
                <label>API Key</label>
            </div>`,
                buttons: [
                    {
                        text: 'Copy',
                        icon: 'copy',
                        class: 'btn-primary',
                        handler: () => {
                            navigator.clipboard.writeText(key);
                            toast.show('API key copied to clipboard', 'success');
                        }
                    }
                ]
            });
        }

        async function toggleApiKey(id, currentState) {
            lockUser();

            const response = await fetch('?action=toggle_api_key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id, active: !currentState })
            });

            const data = await response.json();
            if (data.error) {
                toast.show(data.error, 'error');
                return;
            }

            toast.show(`API key ${currentState ? 'disabled' : 'enabled'} successfully`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        }

        async function deleteApiKey(id) {
            const confirmed = await confirmModal('Are you sure you want to delete this API key?');
            if (!confirmed) return;

            lockUser();

            const response = await fetch('?action=delete_api_key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            if (data.error) {
                toast.show(data.error, 'error');
                return;
            }

            toast.show('API key deleted successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }

        async function regenerateUserApiKey() {
            const confirmed = await confirmModal(
                'Are you sure you want to change your user API key? This will invalidate the old key.'
            );
            if (!confirmed) return;

            lockUser();

            const response = await fetch('?action=regenerate_user_api_key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            if (data.error) {
                toast.show(data.error, 'error');
                return;
            }

            toast.show('User API key changed successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }

        function explain() {
            modal.show({
                title: 'User API Key',
                icon: 'info-circle',
                content: `<p>This is your API Key. If this key is invalid some parts of the UI that uses your API Key instead of Session (Project logs for example) won't work.</p>`,
                buttons: [{
                    icon: 'check',
                    text: 'Close',
                    class: 'btn-primary'
                }]
            });
        }

    </script>
</body>

</html>