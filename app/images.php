<?php
require 'include/lib.php';
$Docker = new DockerManager();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - Images</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "include/header.php"; ?>

    <main class="main-content">
        <div class="project-header-template">
            <div class="breadcrumb">
                <span>Images</span>
            </div>

            <h1 class="page-title">Images</h1>

            <div class="project-overview">
                <div class="project-header">
                    <h3 class="project-title">Docker Images</h3>
                </div>
                <div class="project-details">
                    <p>Manage your Docker images across all projects</p>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active">All Images</div>
            </div>
        </div>

        <div class="tab-content images-tab">
            <div class="action-bar">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="pullImage()">
                        <i class="fas fa-cloud-download-alt"></i>
                        <span>DockerHub Pull</span>
                    </button>
                    <button class="btn btn-secondary" onclick="pullImageManually()">
                        <i class="fas fa-plus"></i>
                        <span>Pull Image</span>
                    </button>
                </div>
            </div>

            <div class="container-list">
                <?php
                $allImages = $Docker->listImages(true);

                foreach ($allImages as $image):
                    $tags = $image['RepoTags'] ?? ['<none>:<none>'];
                    if (in_array('<none>:<none>', $tags)) {
                        continue;
                    }
                    $size = number_format($image['Size'] / (1024 * 1024), 2) . ' MB';
                    $created = date('Y-m-d H:i:s', $image['Created']);
                    $imageId = substr($image['Id'], 7, 12);
                    ?>
                    <div class="container-card image-card" data-id="<?php echo htmlspecialchars($image['Id']); ?>">
                        <div class="container-details">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <div class="container-icon" style="background-image: <?= gradientFromText($tags[0]); ?>">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <div class="container-name-wrapper">
                                    <?php foreach ($tags as $tag): ?>
                                        <div class="container-name"><?php echo htmlspecialchars($tag); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="container-info">
                                <span class="info-item">
                                    <i class="fas fa-hashtag"></i>
                                    <span class="image-id" title="Click to copy full ID" style="cursor:pointer;"
                                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($image['Id']); ?>');">
                                        <?php echo htmlspecialchars($imageId); ?>
                                    </span>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-weight-hanging"></i>
                                    <?php echo htmlspecialchars($size); ?>
                                </span>
                                <span class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo htmlspecialchars($created); ?>
                                </span>
                            </div>
                        </div>
                        <div class="container-actions">
                            <button class="btn btn-secondary"
                                onclick="inspectImage('<?php echo htmlspecialchars($image['Id']); ?>')">
                                <i class="fas fa-search"></i>
                                <span>Inspect</span>
                            </button>
                            <button class="btn btn-danger"
                                onclick="removeImage('<?php echo htmlspecialchars($image['Id']); ?>')">
                                <i class="fas fa-trash"></i>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        let rawJson = null;
        
        async function removeImage(imageId) {
            if (!await confirmModal('Are you sure you want to remove this image?')) return;

            lockUser();

            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'ImageRemove',
                        imageId: imageId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    toast.show('Image removed successfully!', 'success');
                } else {
                    toast.show('Failed to remove image: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error removing image', 'error');
            }
            setTimeout(() => window.location.reload(), 1000);

        }

        async function inspectImage(imageId) {
            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'ImageInspect',
                        imageId: imageId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    modal.show({
                        icon: 'info-circle',
                        title: 'Inspect Image',
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

        function selectImage(imageName) {
            document.getElementById('searchInput').value = imageName;
            document.getElementById('searchResults').innerHTML = '';
            pullImage(imageName);
        }

        async function pullImage(presetName = null) {
            const modalContent = `
        <div class="input">
            <input type="text" id="searchInput" placeholder=" " />
            <label>Search Docker Hub</label>
        </div>
        <div id="searchResults" class="search-results" style="display: none;"></div>
        <div id="pullProgress" class="pull-progress" style="display: none;">
            <div id="pull-status" class="status-message">Connecting...</div>
            <div id="pull-layers" class="layers-container"></div>
        </div>
    `;

            modal.show({
                icon: 'cloud-download-alt',
                title: 'Pull Image',
                content: modalContent,
                size: 'modal-lg',
                buttons: [],
                onShow: () => {
                    const searchInput = document.getElementById('searchInput');
                    let debounceTimer;

                    searchInput.addEventListener('input', (e) => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            searchDockerHub(e.target.value);
                        }, 300);
                    });

                    if (presetName) {
                        searchInput.value = presetName;
                        startPull(presetName);
                    }
                }
            });
        }

        async function searchDockerHub(query) {
            if (!query) {
                document.getElementById('searchResults').style.display = 'none';
                return;
            }

            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'SearchDockerHub',
                        query: query,
                        pageSize: 10,
                        page: 1
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                const resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = '';

                if (data.results && data.results.length > 0) {
                    data.results.forEach(result => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'search-result-item';
                        resultItem.innerHTML = `
                    <div class="search-result-name">
                    ${result.repo_name}
                    ${result.is_official ? '<span class="official-badge">Official</span>' : ''}
                    ${result.is_automated ? '<span class="automated-badge">Automated</span>' : ''}
                    </div>
                    <div class="search-result-description">${result.short_description || 'No description available'}</div>
                    <div class="search-result-meta">
                    <span><i class="fas fa-star"></i> ${result.star_count}</span>
                    <span><i class="fas fa-download"></i> ${result.pull_count}</span>
                    </div>
                `;
                        resultItem.onclick = () => startPull(result.repo_name);
                        resultsDiv.appendChild(resultItem);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div class="search-result-item">No results found</div>';
                    resultsDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error searching Docker Hub:', error);
                toast.show('Error searching Docker Hub', 'error');
            }
        }

        async function startPull(imageName) {
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('searchInput').value = imageName;
            document.getElementById('pullProgress').style.display = 'block';

            if (!imageName.includes(':')) {
                imageName += ':latest';
            }

            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'ImagePull',
                        imageName: imageName,
                        stream: true,
                        apikey: '<?php echo htmlspecialchars($user["api_key"]); ?>'
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error);
                }

                const eventSource = new EventSource(data.streamUrl);
                const layerStatus = {};
                const statusDiv = document.getElementById('pull-status');
                const layersDiv = document.getElementById('pull-layers');

                eventSource.addEventListener('message', (event) => {
                    try {
                        const pullData = JSON.parse(event.data);
                        updatePullProgress(pullData, layerStatus, statusDiv, layersDiv);
                    } catch (e) {
                        console.error('Error parsing pull data:', e);
                    }
                });

                eventSource.addEventListener('progress', (event) => {
                    try {
                        const pullData = JSON.parse(event.data);
                        updatePullProgress(pullData, layerStatus, statusDiv, layersDiv);
                    } catch (e) {
                        console.error('Error parsing progress data:', e);
                    }
                });

                eventSource.addEventListener('error', (event) => {
                    console.error('EventSource error:', event);
                    eventSource.close();
                    statusDiv.textContent = 'Error occurred during pull';
                    toast.show('Error pulling image: Stream disconnected', 'error');
                });

                eventSource.addEventListener('connected', (event) => {
                    console.log('Connected to pull stream:', event.data);
                    statusDiv.textContent = 'Connected to pull stream...';
                });

                eventSource.addEventListener('disconnected', (event) => {
                    console.log('Pull stream ended:', event.data);
                    eventSource.close();
                    statusDiv.textContent = 'Pull completed successfully!';
                    toast.show('Image pulled successfully!', 'success');
                    setTimeout(() => window.location.reload(), 2500);
                });

                eventSource.addEventListener('complete', (event) => {
                    console.log('Pull completed:', event.data);
                    eventSource.close();
                    statusDiv.textContent = 'Pull completed successfully!';
                    toast.show('Image pulled successfully!', 'success');
                    setTimeout(() => window.location.reload(), 2500);
                });

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('pull-status').textContent = 'Error: ' + error.message;
                toast.show('Error pulling image: ' + error.message, 'error');
            }
        }

        function updatePullProgress(pullData, layerStatus, statusDiv, layersDiv) {
            if (!pullData) return;

            if (pullData.status) {
                statusDiv.textContent = pullData.status;
            }

            if (pullData.id && pullData.status) {
                const layerId = pullData.id;

                if (!layerStatus[layerId]) {
                    layerStatus[layerId] = {};

                    const layerElement = document.createElement('div');
                    layerElement.className = 'layer-progress';
                    layerElement.id = `layer-${layerId}`;
                    layerElement.innerHTML = `
                <div class="layer-info">
                    <span class="layer-id">${layerId}</span>
                    <span class="layer-status">${pullData.status}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="layer-details">
                    <span class="progress-text">0%</span>
                </div>
            `;
                    layersDiv.appendChild(layerElement);
                }

                const layerElement = document.getElementById(`layer-${layerId}`);
                if (layerElement) {
                    const statusElement = layerElement.querySelector('.layer-status');
                    const progressFill = layerElement.querySelector('.progress-fill');
                    const progressText = layerElement.querySelector('.progress-text');

                    statusElement.textContent = pullData.status;

                    if (pullData.progressDetail && pullData.progressDetail.total) {
                        const current = pullData.progressDetail.current || 0;
                        const total = pullData.progressDetail.total;
                        const percentage = Math.round((current / total) * 100);

                        progressFill.style.width = `${percentage}%`;
                        progressText.textContent = `${percentage}% (${formatBytes(current)}/${formatBytes(total)})`;
                    } else if (pullData.status === 'Pull complete' || pullData.status === 'Already exists') {
                        progressFill.style.width = '100%';
                        progressText.textContent = '100%';
                        layerElement.classList.add('completed');
                    }
                }

                layerStatus[layerId] = pullData;
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        async function pullImageManually() {
            let imageName = await promptModal('Enter the image name (e.g., nginx:latest):');
            if (!imageName) return;
            imageName = imageName.trim();
            if (!imageName) {
                toast.show('Image name cannot be empty', 'error');
                return;
            }
            pullImage(imageName);
        }
    </script>
</body>

</html>