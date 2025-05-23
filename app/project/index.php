<?php include "include/projectHandler.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - <?= htmlspecialchars(ucfirst($projectName)); ?></title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>
        <div class="tab-content containers-tab">
            <div class="action-bar">
                <div class="action-buttons">
                    <button class="btn btn-danger" title="Stop all containers" onclick="apiCall('all', 'stop')">
                        <i class="fas fa-stop"></i>
                        <span>Stop All</span>
                    </button>
                    <button class="btn btn-secondary" title="Restart all containers"
                        onclick="apiCall('all', 'restart')">
                        <i class="fas fa-sync-alt"></i>
                        <span>Restart All</span>
                    </button>
                    <button class="btn btn-primary" title="Start all containers" onclick="apiCall('all', 'start')">
                        <i class="fas fa-play"></i>
                        <span>Start All</span>
                    </button>
                </div>
            </div>

            <div class="container-list">
                <?php foreach ($projectDetails["containers"] as $container): ?>
                    <div class="container-card" data-id="<?php echo htmlspecialchars($container['id']); ?>">
                        <div class="container-status-indicator <?php echo $container['status']; ?>">
                        </div>
                        <div class="container-details">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <div class="container-icon"
                                    style="background-image: <?= gradientFromText($container['name']); ?>; color: white; text-shadow: 0 0 5px rgba(0, 0, 0, 0.5); font-family: 'Orbitron', sans-serif; font-size: 18px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; overflow: hidden; ">
                                    <?php echo strtoupper(substr(prettifyName($container['name']), 0, 3)); ?>
                                </div>
                                <div class="container-name-wrapper">
                                    <div class="container-name"><?php echo prettifyName($container['name']); ?></div>
                                    <div class="container-badge <?php echo $container['status']; ?>">
                                        <?php echo $container['status']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="container-info">
                                <span class="info-item">
                                    <i class="fas fa-hashtag"></i>
                                    <span class="container-id" title="Click to copy full ID" style="cursor:pointer;"
                                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($container['id']); ?>'); alert('Container ID copied to clipboard!');">
                                        <?php echo htmlspecialchars(substr($container['id'], 0, 12)); ?>...
                                    </span>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-plug"></i>
                                    Ports:
                                    <?php
                                    $portsX = array_map(function ($port) {
                                        $port = explode('->', $port);

                                        if (isset($port[1])) {
                                            return '<a href="http://localhost:' . htmlspecialchars($port[0]) . '" target="_blank">' . htmlspecialchars($port[0]) . '</a> -> ' . htmlspecialchars($port[1]);
                                        } else {
                                            return htmlspecialchars($port[0]);
                                        }
                                    }, $container['ports']);
                                    echo implode(', ', $portsX);
                                    ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <?php
                                    if (!empty($container['uptime'])) {
                                        echo htmlspecialchars($container['uptime']);
                                    } else {
                                        echo htmlspecialchars($container['status']);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="container-actions">
                            <?php if (strtolower($container['status']) === 'running'): ?>
                                <button class="btn btn-secondary" title="Restart container"
                                    onclick="apiCall('<?php echo htmlspecialchars($container['id']); ?>', 'restart')">
                                    <i class="fas fa-sync-alt"></i>
                                    <span>Restart</span>
                                </button>
                                <button class="btn btn-danger" title="Stop container"
                                    onclick="apiCall('<?php echo htmlspecialchars($container['id']); ?>', 'stop')">
                                    <i class="fas fa-stop"></i>
                                    <span>Stop</span>
                                </button>
                                <button class="btn btn-primary" title="Inspect container"
                                    onclick="inspectContainer('<?php echo htmlspecialchars($container['id']); ?>')">
                                    <i class="fas fa-eye"></i>
                                    <span>Inspect</span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary" title="Start container"
                                    onclick="apiCall('<?php echo htmlspecialchars($container['id']); ?>', 'start')">
                                    <i class="fas fa-play"></i>
                                    <span>Start</span>
                                </button>
                                <button class="btn btn-danger" title="Remove container"
                                    onclick="removeContainer('<?php echo htmlspecialchars($container['id']); ?>')">
                                    <i class="fas fa-trash"></i>
                                    <span>Remove</span>
                                </button>
                                <button class="btn btn-secondary" title="Inspect container"
                                    onclick="inspectContainer('<?php echo htmlspecialchars($container['id']); ?>')">
                                    <i class="fas fa-eye"></i>
                                    <span>Inspect</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    </div>
    <script>
        async function apiCall(containerId, action) {
            const actionMap = {
                'start': 'ContainerStart',
                'stop': 'ContainerStop',
                'restart': 'ContainerRestart'
            };
            const apiAction = actionMap[action] || action;

            if (containerId === 'all') {
                if (!await confirmModal('Are you sure you want to perform this action on all containers?')) {
                    return;
                }

                lockUser();

                const containers = document.querySelectorAll('.container-card');
                for (const container of containers) {
                    const id = container.dataset.id;
                    const name = container.querySelector('.container-name').textContent;
                    try {
                        const response = await fetch('/project/api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: apiAction, containerId: id })
                        });
                        const data = await response.json();

                        updateContainerUI(container, action);

                        if (data.success === false) throw new Error(data.error || 'Unknown error');
                        await toast.show(`${action.charAt(0).toUpperCase() + action.slice(1)}ed container: ${name}`, 'success');
                    } catch (error) {
                        await toast.show(`Failed to ${action} container: ${name}`, 'error');
                        console.error('Error:', error);
                    }
                }
                toast.show('All containers ' + action + 'ed successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (!await confirmModal('Are you sure you want to perform this action on this container?')) {
                    return;
                }

                const containerCard = document.querySelector(`[data-id="${containerId}"]`);
                const containerName = containerCard.querySelector('.container-name').textContent;

                lockUser();

                try {
                    const response = await fetch('/project/api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: apiAction, containerId: containerId })
                    });
                    const data = await response.json();

                    updateContainerUI(containerCard, action);

                    if (data.success === false) throw new Error(data.error || 'Unknown error');
                    await toast.show(`${action.charAt(0).toUpperCase() + action.slice(1)}ed container: ${containerName}`, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } catch (error) {
                    await toast.show(`Failed to ${action} container: ${containerName}`, 'error');
                    console.error('Error:', error);
                }
            }
        }

        function updateContainerUI(container, action) {
            const statusIndicator = container.querySelector('.container-status-indicator');
            const statusBadge = container.querySelector('.container-badge');
            const actionsDiv = container.querySelector('.container-actions');

            if (action === 'stop' || action === 'ContainerStop') {
                statusIndicator.className = 'container-status-indicator stopped';
                statusBadge.className = 'container-badge stopped';
                statusBadge.textContent = 'stopped';
                actionsDiv.innerHTML = `
            <button class="btn btn-primary" title="Start container" onclick="apiCall('${container.dataset.id}', 'ContainerStart')">
                <i class="fas fa-play"></i>
                <span>Start</span>
            </button>
            <button class="btn btn-danger" title="Remove container" onclick="removeContainer('${container.dataset.id}')">
                <i class="fas fa-trash"></i>
                <span>Remove</span>
            </button>
            <button class="btn btn-secondary" title="Inspect container" onclick="inspectContainer('${container.dataset.id}')">
                <i class="fas fa-eye"></i>
                <span>Inspect</span>
            </button>
        `;
            } else if (action === 'start' || action === 'ContainerStart') {
                statusIndicator.className = 'container-status-indicator running';
                statusBadge.className = 'container-badge running';
                statusBadge.textContent = 'running';
                actionsDiv.innerHTML = `
            <button class="btn btn-secondary" title="Restart container" onclick="apiCall('${container.dataset.id}', 'ContainerRestart')">
                <i class="fas fa-sync-alt"></i>
                <span>Restart</span>
            </button>
            <button class="btn btn-danger" title="Stop container" onclick="apiCall('${container.dataset.id}', 'ContainerStop')">
                <i class="fas fa-stop"></i>
                <span>Stop</span>
            </button>
            <button class="btn btn-secondary" title="Inspect container" onclick="inspectContainer('${container.dataset.id}')">
                <i class="fas fa-eye"></i>
                <span>Inspect</span>
            </button>
        `;
            }
        }

        async function removeContainer(containerId) {
            const result = await modal.show({
                icon: 'trash',
                title: 'Remove Container',
                content: `
            <p>How would you like to remove this container?</p>
            <div class="checkbox-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="force-remove">
                    <label for="force-remove">
                        Force remove (Kill container if running)
                    </label>
                </div>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remove-volumes">
                    <label for="remove-volumes">
                        Remove associated volumes
                    </label>
                </div>
            </div>
        `,
                buttons: [
                    {
                        icon: 'times',
                        text: 'Cancel',
                        class: 'btn-secondary'
                    },
                    {
                        icon: 'trash',
                        text: 'Remove',
                        class: 'btn-danger',
                        handler: () => {
                            const force = document.getElementById('force-remove').checked ? true : false;
                            const volumes = document.getElementById('remove-volumes').checked ? true : false;

                            fetch('api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'ContainerRemove',
                                    containerId: containerId,
                                    force: force,
                                    volumes: volumes
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success === true) {
                                        toast.show('Container removed successfully!', 'success');
                                        setTimeout(() => window.location.reload(), 1000);
                                    } else {
                                        toast.show('Failed to remove container.', 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    toast.show('Error removing container.', 'error');
                                });
                        }
                    }
                ]
            });
        }

        let rawJson = null;

        async function inspectContainer(containerId) {
            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'ContainerInspect',
                        containerId: containerId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    modal.show({
                        icon: 'info-circle',
                        title: 'Inspect Container',
                        content: `
                            <div style="margin-bottom: 10px;">
                                <button class="btn btn-sm btn-secondary" onclick="copyRawJson()">
                                    <i class="fas fa-copy"></i> Copy Raw JSON
                                </button>
                            </div>
                            <div id="network-json-tree" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 14px;"></div>
                        `,
                        size: "modal-lg",
                        onShow: () => {
                            const treeContainer = document.getElementById('network-json-tree');
                            if (treeContainer) {
                                treeContainer.innerHTML = '';
                                renderTree(treeContainer, data.details);
                            }
                        },
                        buttons: [{
                            icon: 'times',
                            text: 'Close',
                            class: 'btn-secondary'
                        }]
                    });
                    rawJson = JSON.stringify(data.details, null, 2);
                } else {
                    toast.show('Failed to inspect container: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error inspecting container', 'error');
            }
        }

        function copyRawJson() {
            if (rawJson) {
                navigator.clipboard.writeText(rawJson).then(() => {
                    toast.show('Raw JSON copied to clipboard!', 'success');
                }).catch(err => {
                    console.error('Error copying text: ', err);
                    toast.show('Failed to copy raw JSON', 'error');
                });
            } else {
                toast.show('No raw JSON available', 'error');
            }
        }
    </script>
</body>

</html>