<div class="project-header-template">
    <div class="breadcrumb">
        <a href="/">Projects</a>
        <span>›</span>
        <span><?php echo htmlspecialchars(ucfirst($projectName)); ?></span>
    </div>

    <h1 class="page-title"><?php echo htmlspecialchars(ucfirst($projectName)); ?></h1>

    <div class="project-overview">
        <div class="project-header">
            <h3 class="project-title"><?php echo htmlspecialchars(ucfirst($projectName)); ?></h3>
            <?php if (!empty($projectDetails['status']) && strtolower($projectDetails['status']) === 'running'): ?>
                <span class="project-status status-running">Running</span>
            <?php else: ?>
                <span class="project-status status-stopped">Stopped</span>
            <?php endif; ?>
        </div>
        <div class="project-details" style="display: flex; align-items: center; justify-content: space-between;">
            <p><?php echo htmlspecialchars($ProjectData->get("description") ?? 'No description available'); ?>
            </p><button class="btn btn-secondary" onclick="setProjectDescription()"><i class="fas fa-edit"></i>
                <span>Edit Description</span></button>
        </div>

        <?php if ($projectName == "leragon" && !isset($_COOKIE['dissmissLeragonWarning'])) { ?>
            <div class="alert alert-danger" style="margin-top: 10px;">
                <i class="fas fa-exclamation-triangle"></i>
                <p><strong>Warning:</strong> This is the Leragon project. The manager you are using right now.</p>
                <button class="btn btn-secondary" onclick="dissmissLeragonWarning()"><i class="fas fa-times"></i>
                    <span>Dismiss</span></button>
            </div>
        <?php } ?>
    </div>

    <div class="tabs">
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"
            href="/project/?name=<?= htmlspecialchars($projectName) ?>">Containers</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'images.php' ? 'active' : '' ?>"
            href="/project/images.php?name=<?= htmlspecialchars($projectName) ?>">Images</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'networks.php' ? 'active' : '' ?>"
            href="/project/networks.php?name=<?= htmlspecialchars($projectName) ?>">Networks</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'volumes.php' ? 'active' : '' ?>"
            href="/project/volumes.php?name=<?= htmlspecialchars($projectName) ?>">Volumes</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>"
            href="/project/logs.php?name=<?= htmlspecialchars($projectName) ?>">Logs</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>"
            href="/project/settings.php?name=<?= htmlspecialchars($projectName) ?>">Settings</a>
    </div>
</div>

<script>

    async function setProjectDescription() {
        const currentDescription = '<?php echo htmlspecialchars($ProjectData->get("description") ?? 'No description available'); ?>';
        const description = await promptModal('Set New Project Description', currentDescription, 'Description');
        if (description) {
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ description: description, action: 'ContainerSetDescription', projectName: '<?php echo htmlspecialchars($projectName); ?>' })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success === true) {
                        toast.show('Project description updated successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        toast.show('Failed to update project description.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toast.show('Error updating project description.', 'error');
                });
        }
    }

    function dissmissLeragonWarning() {
        document.querySelector('.alert').style.display = 'none';
        document.cookie = "dissmissLeragonWarning=true; max-age=31536000; path=/";
    }
</script>