<div class="project-header-template">
    <div class="breadcrumb">
        <span>Settings</span>
    </div>

    <h1 class="page-title">Settings</h1>

    <div class="project-overview">
        <div class="project-header">
            <h3 class="project-title">Leragon Settings</h3>
        </div>
        <div class="project-details">
            <p>
                Manage your Leragon settings here. You can configure various options to customize your experience.
            </p>
        </div>
    </div>

    
    <div class="tabs">
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>"
            href="/settings/settings.php">Settings</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'apikeys.php' ? 'active' : '' ?>"
            href="/settings/apikeys.php">API Keys</a>
        <a class="tab <?= basename($_SERVER['PHP_SELF']) == 'github.php' ? 'active' : '' ?>"
            href="/settings/github.php">Github</a>
    </div>
</div>