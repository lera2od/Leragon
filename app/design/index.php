<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Manager</title>
        <link rel="stylesheet" href="style.css">

</head>
<body>
    <!-- Header/Navigation -->
    <header class="header">
        <a href="/" class="logo">Lera<span>gon</span></a>
        <nav class="user-nav">
            <a href="#">Documentation</a>
            <a href="#">Settings</a>
            <a href="#">Account</a>
        </nav>
    </header>

    <div class="container">
        <!-- Sidebar -->
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

        <!-- Main Content - Projects Dashboard -->
        <main class="main-content">
            <h1 class="page-title">Docker Projects</h1>
            
            <div class="projects-grid">
                <!-- Project Card 1 -->
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title">Web Application</h3>
                        <span class="project-status status-running">Running</span>
                    </div>
                    <div class="project-details">
                        <p>Production environment for the main web application</p>
                    </div>
                    <div class="project-metrics">
                        <span>5 containers</span>
                        <span>Updated 2 hours ago</span>
                    </div>
                </div>
                
                <!-- Project Card 2 -->
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title">Database Cluster</h3>
                        <span class="project-status status-running">Running</span>
                    </div>
                    <div class="project-details">
                        <p>PostgreSQL and Redis cluster for data storage</p>
                    </div>
                    <div class="project-metrics">
                        <span>3 containers</span>
                        <span>Updated 1 day ago</span>
                    </div>
                </div>
                
                <!-- Project Card 3 -->
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title">API Services</h3>
                        <span class="project-status status-stopped">Stopped</span>
                    </div>
                    <div class="project-details">
                        <p>REST API services for external integrations</p>
                    </div>
                    <div class="project-metrics">
                        <span>4 containers</span>
                        <span>Updated 3 days ago</span>
                    </div>
                </div>
                
                <!-- Project Card 4 -->
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title">Test Environment</h3>
                        <span class="project-status status-running">Running</span>
                    </div>
                    <div class="project-details">
                        <p>Staging environment for QA and testing</p>
                    </div>
                    <div class="project-metrics">
                        <span>6 containers</span>
                        <span>Updated 5 hours ago</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Project Detail Page Templates (Initially Hidden - Shown when clicking on a project) -->
    <div style="display: none;">
        <!-- Common Project Header Template -->
        <div class="project-header-template">
            <div class="breadcrumb">
                <a href="#">Projects</a>
                <span>›</span>
                <span>Web Application</span>
            </div>
            
            <h1 class="page-title">Web Application</h1>
            
            <div class="project-overview">
                <div class="project-header">
                    <h3 class="project-title">Web Application</h3>
                    <span class="project-status status-running">Running</span>
                </div>
                <div class="project-details">
                    <p>Production environment for the main web application</p>
                </div>
            </div>
            
            <div class="tabs">
                <div class="tab active">Containers</div>
                <div class="tab">Images</div>
                <div class="tab">Networks</div>
                <div class="tab">Volumes</div>
                <div class="tab">Logs</div>
                <div class="tab">Settings</div>
            </div>
        </div>

        <!-- Containers Tab Template -->
        <div class="tab-content containers-tab">
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" placeholder="Search containers..." class="search-input">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary">Create Container</button>
                </div>
            </div>
            
            <div class="container-list">
                <!-- Container Template -->
                <div class="container-card">
                    <div class="container-icon">C</div>
                    <div class="container-details">
                        <div class="container-name">webapp-frontend</div>
                        <div class="container-info">
                            <span>ID: abc123</span>
                            <span>Port: 80:80</span>
                            <span>Running for 2 days</span>
                        </div>
                    </div>
                    <div class="container-actions">
                        <button class="btn btn-secondary">Restart</button>
                        <button class="btn btn-danger">Stop</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Images Tab Template -->
        <div class="tab-content images-tab">
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" placeholder="Search images..." class="search-input">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary">Pull Image</button>
                    <button class="btn btn-secondary">Build Image</button>
                </div>
            </div>
            
            <div class="resource-list">
                <!-- Image Template -->
                <div class="resource-card">
                    <div class="resource-icon">I</div>
                    <div class="resource-details">
                        <div class="resource-name">nginx:latest</div>
                        <div class="resource-info">
                            <span>ID: sha256:123456</span>
                            <span>Size: 142MB</span>
                            <span>Created: 5 days ago</span>
                        </div>
                    </div>
                    <div class="resource-actions">
                        <button class="btn btn-secondary">Create Container</button>
                        <button class="btn btn-danger">Remove</button>
                    </div>
                </div>
                
                <!-- Another Image Template -->
                <div class="resource-card">
                    <div class="resource-icon">I</div>
                    <div class="resource-details">
                        <div class="resource-name">postgres:13</div>
                        <div class="resource-info">
                            <span>ID: sha256:789012</span>
                            <span>Size: 314MB</span>
                            <span>Created: 2 weeks ago</span>
                        </div>
                    </div>
                    <div class="resource-actions">
                        <button class="btn btn-secondary">Create Container</button>
                        <button class="btn btn-danger">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Networks Tab Template -->
        <div class="tab-content networks-tab">
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" placeholder="Search networks..." class="search-input">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary">Create Network</button>
                </div>
            </div>
            
            <div class="resource-list">
                <!-- Network Template -->
                <div class="resource-card">
                    <div class="resource-icon">N</div>
                    <div class="resource-details">
                        <div class="resource-name">webapp-network</div>
                        <div class="resource-info">
                            <span>Driver: bridge</span>
                            <span>Subnet: 172.18.0.0/16</span>
                            <span>Containers: 5</span>
                        </div>
                    </div>
                    <div class="resource-actions">
                        <button class="btn btn-secondary">Inspect</button>
                        <button class="btn btn-danger">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volumes Tab Template -->
        <div class="tab-content volumes-tab">
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" placeholder="Search volumes..." class="search-input">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary">Create Volume</button>
                </div>
            </div>
            
            <div class="resource-list">
                <!-- Volume Template -->
                <div class="resource-card">
                    <div class="resource-icon">V</div>
                    <div class="resource-details">
                        <div class="resource-name">postgres-data</div>
                        <div class="resource-info">
                            <span>Driver: local</span>
                            <span>Mountpoint: /var/lib/docker/volumes/...</span>
                            <span>Used by: db-postgres</span>
                        </div>
                    </div>
                    <div class="resource-actions">
                        <button class="btn btn-secondary">Inspect</button>
                        <button class="btn btn-danger">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Tab Template -->
        <div class="tab-content logs-tab">
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" placeholder="Search logs..." class="search-input">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-secondary">Refresh</button>
                    <button class="btn btn-secondary">Download</button>
                </div>
            </div>
            
            <div class="logs-container">
                <div class="log-filters">
                    <select class="select-input">
                        <option value="">All Containers</option>
                        <option value="webapp-frontend">webapp-frontend</option>
                        <option value="webapp-backend">webapp-backend</option>
                    </select>
                    <select class="select-input">
                        <option value="">All Levels</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                
                <div class="log-viewer">
                    <div class="log-entry log-info">
                        <span class="log-timestamp">2025-05-14 10:15:23</span>
                        <span class="log-container">webapp-frontend</span>
                        <span class="log-message">Server started successfully on port 80</span>
                    </div>
                    <div class="log-entry log-warning">
                        <span class="log-timestamp">2025-05-14 10:15:20</span>
                        <span class="log-container">webapp-backend</span>
                        <span class="log-message">Connection pool approaching limit (80%)</span>
                    </div>
                    <div class="log-entry log-error">
                        <span class="log-timestamp">2025-05-14 10:14:55</span>
                        <span class="log-container">redis-cache</span>
                        <span class="log-message">Failed to connect to cache server: timeout</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab Template -->
        <div class="tab-content settings-tab">
            <div class="settings-container">
                <div class="settings-section">
                    <h3 class="settings-title">Project Settings</h3>
                    
                    <div class="settings-form">
                        <div class="form-group">
                            <label>Project Name</label>
                            <input type="text" value="Web Application" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-textarea">Production environment for the main web application</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Environment</label>
                            <select class="form-select">
                                <option value="production">Production</option>
                                <option value="staging">Staging</option>
                                <option value="development">Development</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Auto-restart</label>
                            <div class="toggle-switch">
                                <input type="checkbox" id="auto-restart" checked>
                                <label for="auto-restart"></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Resource Limits</label>
                            <div class="resource-limits">
                                <div class="limit-item">
                                    <span>CPU</span>
                                    <input type="range" min="0" max="100" value="50" class="slider">
                                    <span>50%</span>
                                </div>
                                <div class="limit-item">
                                    <span>Memory</span>
                                    <input type="range" min="0" max="100" value="70" class="slider">
                                    <span>70%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button class="btn btn-primary">Save Changes</button>
                            <button class="btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3 class="settings-title">Danger Zone</h3>
                    
                    <div class="danger-zone">
                        <div class="danger-action">
                            <div class="danger-details">
                                <h4>Delete Project</h4>
                                <p>Once you delete a project, there is no going back. All containers, networks, and volumes will be removed.</p>
                            </div>
                            <button class="btn btn-danger">Delete Project</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>