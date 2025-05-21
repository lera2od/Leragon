<?php include "../include/projectHandler.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - <?= htmlspecialchars(ucfirst($projectName)); ?> - Networks</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "../include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>
        <div class="tab-content networks-tab">
            <div class="container-list">
                <?php
                $Docker = new DockerManager();
                $allNetworks = $Docker->listNetworks();

                $networks = array_filter($allNetworks, function ($network) use ($projectName) {
                    $projectFromLabel = isset($network['Labels']['com.docker.compose.project']) ? $network['Labels']['com.docker.compose.project'] : '';
                    $projectFromName = strpos($network['Name'], "{$projectName}_") === 0;
                    return $projectFromLabel === $projectName || $projectFromName;
                });

                foreach ($networks as $network):
                    $networkId = substr($network['Id'], 0, 12);
                    $networkName = $network['Name'];
                    $driver = $network['Driver'];
                    $scope = $network['Scope'];
                    $containers = [];
                    if (is_array($network['Containers']) && count($network['Containers']) > 0) {
                        foreach ($network['Containers'] as $containerId => $containerInfo) {
                            $containers[] = [
                                'Id' => $containerId,
                                'Name' => $containerInfo['Name'] ?? $containerId
                            ];
                        }
                    }
                ?>
                    <div class="container-card network-card" data-id="<?php echo htmlspecialchars($network['Id']); ?>">
                        <div class="container-details">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <div class="container-icon" style="background-image: <?= gradientFromText($networkName); ?>">
                                    <i class="fas fa-network-wired"></i>
                                </div>
                                <div class="container-name-wrapper">
                                    <div class="container-name"><?php echo htmlspecialchars($networkName); ?></div>
                                    <div class="container-badge info">
                                        <?php echo htmlspecialchars($driver); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="container-info">
                                <span class="info-item">
                                    <i class="fas fa-hashtag"></i>
                                    <span class="network-id" title="Click to copy full ID" style="cursor:pointer;"
                                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($network['Id']); ?>');">
                                        <?php echo htmlspecialchars($networkId); ?>
                                    </span>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-globe"></i>
                                    <?php echo htmlspecialchars($scope); ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-cube"></i>
                                    <?php echo count($containers); ?> container<?php echo count($containers) !== 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($containers)): ?>
                            <div class="project-containers">
                                <?php foreach ($containers as $container): ?>
                                    <div class="container-item">
                                        <i class="fas fa-cube"></i>
                                        <?php echo htmlspecialchars($container['Name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="container-actions">
                            <button class="btn btn-secondary btn-sm" onclick="inspectNetwork('<?php echo htmlspecialchars($network['Id']); ?>')">
                                <i class="fas fa-info-circle"></i>
                                <span>Inspect</span>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="removeNetwork('<?php echo htmlspecialchars($network['Id']); ?>')">
                                <i class="fas fa-trash"></i>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($networks)): ?>
                    <div class="container-card" style="text-align: center; padding: 20px;">
                        <i class="fas fa-network-wired" style="font-size: 24px; color: var(--text-secondary); margin-bottom: 10px;"></i>
                        <p style="color: var(--text-secondary);">No networks found for this project</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        async function removeNetwork(networkId) {
            if (!await confirmModal('Are you sure you want to remove this network?')) {
                return;
            }

            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'NetworkRemove',
                        networkId: networkId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    toast.show('Network removed successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    toast.show('Failed to remove network: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error removing network', 'error');
            }
        }

        let rawJson;

        async function inspectNetwork(networkId) {
            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'NetworkInspect',
                        networkId: networkId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    modal.show({
                        icon: 'info-circle',
                        title: 'Network Details',
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
                            function renderTree(container, obj, path = '') {
                                for (const key in obj) {
                                    if (!obj.hasOwnProperty(key)) continue;
                                    const value = obj[key];
                                    const nodeId = path + key.replace(/[^a-zA-Z0-9_]/g, '_');
                                    if (typeof value === 'object' && value !== null) {
                                        const details = document.createElement('details');
                                        details.style.marginLeft = '20px';
                                        details.style.padding = '4px 0';
                                        const summary = document.createElement('summary');
                                        summary.style.cursor = 'pointer';
                                        summary.style.color = 'var(--text-primary)';
                                        summary.style.fontWeight = 'bold';
                                        summary.textContent = key;
                                        details.appendChild(summary);
                                        renderTree(details, value, nodeId + '_');
                                        container.appendChild(details);
                                    } else {
                                        const div = document.createElement('div');
                                        div.style.marginLeft = '20px';
                                        div.style.padding = '4px 0';
                                        div.style.color = 'var(--text-secondary)';
                                        const keySpan = document.createElement('span');
                                        keySpan.style.color = 'var(--text-primary)';
                                        keySpan.style.fontWeight = 'bold';
                                        keySpan.textContent = key + ': ';
                                        div.appendChild(keySpan);
                                        div.appendChild(document.createTextNode(String(value)));
                                        container.appendChild(div);
                                    }
                                }
                            }
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
                    toast.show('Failed to inspect network: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error inspecting network', 'error');
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