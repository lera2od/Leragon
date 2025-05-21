<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="/include/toast.js"></script>
<script src="/include/modal.js"></script>

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
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item active">Projects</li>
            <li class="sidebar-menu-item">Images</li>
            <li class="sidebar-menu-item">Networks</li>
            <li class="sidebar-menu-item">Volumes</li>
            <li class="sidebar-menu-item">Logs</li>
            <li class="sidebar-menu-item">Settings</li>
        </ul>
    </aside>