<?php include "include/projectHandler.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - <?= htmlspecialchars(ucfirst($projectName)); ?> - Volumes</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>
        <div class="tab-content volumes-tab">
            <div class="container-list">
                <?php
                $Docker = new DockerManager();
                $allVolumes = $Docker->listVolumes();

                $volumes = array_filter($allVolumes, function ($volume) use ($projectName) {
                    $labels = $volume['Labels'] ?? [];
                    $projectLabel = $labels['com.docker.compose.project'] ?? '';
                    $volumeName = $volume['Name'] ?? '';
                    return $projectLabel === $projectName || strpos($volumeName, "{$projectName}_") === 0;
                });

                foreach ($volumes as $volume):
                    $volumeName = $volume['Name'];
                    $driver = $volume['Driver'];
                    $mountpoint = $volume['Mountpoint'];
                    $labels = $volume['Labels'] ?? [];
                ?>
                    <div class="container-card volume-card" data-id="<?php echo htmlspecialchars($volumeName); ?>">
                        <div class="container-details">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <div class="container-icon" style="background-image: <?= gradientFromText($volumeName); ?>">
                                    <i class="fas fa-hdd"></i>
                                </div>
                                <div class="container-name-wrapper">
                                    <div class="container-name"><?php echo htmlspecialchars($volumeName); ?></div>
                                    <div class="container-badge info">
                                        <?php echo htmlspecialchars($driver); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="container-info">
                                <span class="info-item">
                                    <i class="fas fa-folder"></i>
                                    <span class="volume-mountpoint" title="Click to copy" style="cursor:pointer;"
                                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($mountpoint); ?>');">
                                        <?php echo htmlspecialchars($mountpoint); ?>
                                    </span>
                                </span>
                            </div>
                            
                            <?php if (!empty($labels)): ?>
                            <div class="volume-labels">
                                <?php foreach ($labels as $key => $value): ?>
                                    <div class="label-item">
                                        <span class="label-key"><?php echo htmlspecialchars($key); ?></span>
                                        <span class="label-value"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="container-actions">
                            <button class="btn btn-secondary btn-sm" onclick="inspectVolume('<?php echo htmlspecialchars($volumeName); ?>')">
                                <i class="fas fa-info-circle"></i>
                                <span>Inspect</span>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="removeVolume('<?php echo htmlspecialchars($volumeName); ?>')">
                                <i class="fas fa-trash"></i>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($volumes)): ?>
                    <div class="container-card" style="text-align: center; padding: 20px;">
                        <i class="fas fa-hdd" style="font-size: 24px; color: var(--text-secondary); margin-bottom: 10px;"></i>
                        <p style="color: var(--text-secondary);">No volumes found for this project</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        async function removeVolume(volumeName) {
            const result = await modal.show({
                icon: 'trash',
                title: 'Remove Volume',
                content: `
                    <p>Are you sure you want to remove this volume?</p>
                    <div class="checkbox-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="force-remove">
                            <label for="force-remove">
                                Force remove
                            </label>
                        </div>
                    </div>
                    <p style="color: var(--danger-text); margin-top: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> Warning: This action is irreversible and will delete all data in the volume.
                    </p>
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
                        handler: async () => {
                            const force = document.getElementById('force-remove').checked;
                            
                            try {
                                const response = await fetch('/project/api.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        action: 'VolumeRemove',
                                        volumeName: volumeName,
                                        force: force
                                    })
                                });

                                const data = await response.json();
                                if (data.success) {
                                    toast.show('Volume removed successfully!', 'success');
                                    setTimeout(() => window.location.reload(), 1000);
                                } else {
                                    toast.show('Failed to remove volume: ' + data.error, 'error');
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                toast.show('Error removing volume', 'error');
                            }
                        }
                    }
                ]
            });
        }

        let rawJson;

        async function inspectVolume(volumeName) {
            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'VolumeInspect',
                        volumeName: volumeName
                    })
                });

                const data = await response.json();
                if (data.success) {
                    modal.show({
                        icon: 'info-circle',
                        title: 'Volume Details',
                        content: `
                            <div style="margin-bottom: 10px;">
                                <button class="btn btn-sm btn-secondary" onclick="copyRawJson()">
                                    <i class="fas fa-copy"></i> Copy Raw JSON
                                </button>
                            </div>
                            <div id="volume-json-tree" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 14px;"></div>
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
                            const treeContainer = document.getElementById('volume-json-tree');
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
                    toast.show('Failed to inspect volume: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error inspecting volume', 'error');
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