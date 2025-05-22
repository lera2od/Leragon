<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="/include/lib.js"></script>

<header class="header">
    <a href="/" class="logo">Lera<span>gon</span></a>
    <nav class="navbar">
        <ul class="navbar-menu">
            <li class="navbar-menu-item theme-selector" style="padding: 0 10px">
                <a class="navbar-menu-link" href="#"><i class="fas fa-adjust"></i></a>
                <div class="theme-popup">
                    <div class="theme-option default" onclick="setTheme('default')">
                        <div class="theme-circle">
                        </div>
                        <span>System</span>
                    </div>
                    <div class="theme-divider"></div>
                    <div class="theme-option dark" onclick="setTheme('dark-theme')">
                        <div class="theme-circle">
                        </div>
                        <span>Dark</span>
                    </div>
                    <div class="theme-option light" onclick="setTheme('light-theme')">
                        <div class="theme-circle">
                        </div>
                        <span>Light</span>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
</header>

<script>
    function setTheme(theme) {
        if (theme === 'default') {
            localStorage.removeItem('theme');
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.className = "dark-theme";
            } else {
                document.body.className = "light-theme";
            }
        } else {
            localStorage.setItem('theme', theme);
            document.body.className = theme;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'default';

        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', ({ matches }) => {
                const savedTheme = localStorage.getItem('theme') || 'default';
                if (savedTheme === 'default') {
                    if (matches) {
                        document.body.className = "dark-theme";
                    } else {
                        document.body.className = "light-theme";
                    }
                }
            });

        if (savedTheme === 'default') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.className = "dark-theme";
            } else {
                document.body.className = "light-theme";
            }
        } else {
            document.body.className = savedTheme;
        }
    });
</script>

<div class="container">
    <?php
    $sidebarMenu = [
        ['label' => 'Projects', 'url' => '/'],
        ['label' => 'Images', 'url' => null],
        ['label' => 'Networks', 'url' => null],
        ['label' => 'Volumes', 'url' => null],
        ['label' => 'Logs', 'url' => null],
        ['label' => 'Settings', 'url' => '/settings'],
    ];

    $currentUrl = $_SERVER['REQUEST_URI'];
    $activeFound = false;
    ?>
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <?php foreach ($sidebarMenu as $item): ?>
                <?php
                    $isActive = false;
                    if (!$activeFound && $item['url']) {
                        if ($item['url'] === '/') {
                            $isActive = $currentUrl === '/';
                        } else {
                            $isActive = strpos($currentUrl, $item['url']) === 0;
                        }
                        if ($isActive) {
                            $activeFound = true;
                        }
                    }
                ?>
                <?php if ($item['url']): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>">
                <?php endif; ?>
                <li class="sidebar-menu-item<?= $isActive ? ' active' : '' ?>">
                    <?= htmlspecialchars($item['label']) ?>
                </li>
                <?php if ($item['url']): ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </aside>