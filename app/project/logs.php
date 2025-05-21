<?php include "../include/projectHandler.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - <?= htmlspecialchars(ucfirst($projectName)); ?> - Logs</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <?php include "../include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>
        <div class="tab-content logs-tab">
            <div class="logs-controls">
                <div class="logs-filters">
                    <select id="container-select" class="form-select">
                        <option value="">Select container</option>
                        <?php foreach ($projectDetails["containers"] as $container): ?>
                            <option value="<?php echo htmlspecialchars($container['id']); ?>">
                                <?php echo htmlspecialchars($container['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="logs-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="auto-scroll" checked>
                            <span>Auto-scroll</span>
                        </label>
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="follow-logs" checked>
                            <span>Follow logs</span>
                        </label>
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="show-timestamps">
                            <span>Show timestamps</span>
                        </label>
                    </div>
                    <div class="logs-tail">
                        <label>Show last</label>
                        <select id="tail-lines" class="form-select">
                            <option value="100">100 lines</option>
                            <option value="500">500 lines</option>
                            <option value="1000">1000 lines</option>
                            <option value="all">All lines</option>
                        </select>
                    </div>
                </div>
                <div class="logs-actions">
                    <button class="btn btn-secondary btn-sm" onclick="clearLogs()">
                        <i class="fas fa-eraser"></i>
                        <span>Clear</span>
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="downloadLogs()">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </button>
                </div>
            </div>
            <div id="logs-container" class="logs-container">
                <div id="logs-content" class="logs-content"></div>
            </div>
        </div>
    </main>

    <script>
        let currentContainer = null;
        let logStream = null;
        const logsContent = document.getElementById('logs-content');
        const containerSelect = document.getElementById('container-select');
        const autoScrollCheckbox = document.getElementById('auto-scroll');
        const followLogsCheckbox = document.getElementById('follow-logs');
        const showTimestampsCheckbox = document.getElementById('show-timestamps');
        const tailLinesSelect = document.getElementById('tail-lines');

        containerSelect.addEventListener('change', (e) => {
            currentContainer = e.target.value;
            if (currentContainer) {
                startLogging();
            } else {
                stopLogging();
            }
        });

        function clearLogs() {
            logsContent.innerHTML = '';
        }

        async function downloadLogs() {
            if (!currentContainer) {
                toast.show('Please select a container first', 'error');
                return;
            }

            try {
                const response = await fetch('/project/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'ContainerLogs',
                        containerId: currentContainer,
                        tail: tailLinesSelect.value,
                        timestamps: true
                    })
                });

                const data = await response.json();
                if (data.success) {
                    const blob = new Blob([data.logs], { type: 'text/plain' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `container-${currentContainer}-logs.txt`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error downloading logs:', error);
                toast.show('Failed to download logs: ' + error.message, 'error');
            }
        }

        async function startLogging() {
            stopLogging();
            clearLogs();

            try {
                const evtSource = new EventSource(`/project/stream.php?action=logs&container=${currentContainer}&timestamps=${showTimestampsCheckbox.checked}&tail=${tailLinesSelect.value}`);
                logStream = evtSource;

                evtSource.onmessage = (event) => {
                    const line = document.createElement('div');
                    line.className = 'log-line';
                    line.textContent = event.data;
                    logsContent.appendChild(line);

                    if (autoScrollCheckbox.checked) {
                        logsContent.scrollTop = logsContent.scrollHeight;
                    }
                };

                evtSource.onerror = (error) => {
                    console.error('EventSource failed:', error);
                    stopLogging();
                    toast.show('Log stream connection failed', 'error');
                };
            } catch (error) {
                console.error('Error starting log stream:', error);
                toast.show('Failed to start log stream: ' + error.message, 'error');
            }
        }

        function stopLogging() {
            if (logStream) {
                logStream.close();
                logStream = null;
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', stopLogging);

        // Handle option changes
        followLogsCheckbox.addEventListener('change', (e) => {
            if (currentContainer) {
                if (e.target.checked) {
                    startLogging();
                } else {
                    stopLogging();
                }
            }
        });

        showTimestampsCheckbox.addEventListener('change', () => {
            if (currentContainer && followLogsCheckbox.checked) {
                startLogging();
            }
        });

        tailLinesSelect.addEventListener('change', () => {
            if (currentContainer && followLogsCheckbox.checked) {
                startLogging();
            }
        });
    </script>
</body>
</html>