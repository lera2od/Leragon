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
    <title>Leragon - Docker Manager</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include "include/header.php"; ?>


    <main class="main-content">
        <div class="project-header-template">
            <div class="breadcrumb">
                <span>Projects</span>
            </div>

            <h1 class="page-title">Projects</h1>

            <div class="project-overview">
                <div class="project-header">
                    <h3 class="project-title">Docker Projects</h3>
                </div>
                <div class="project-details">
                    <p>Manage your Docker projects and containers</p>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active">Projects</div>
            </div>
        </div>

        <div class="tab-content containers-tab">
            <div class="action-bar">
                <div class="action-buttons">
                    <button class="btn btn-primary" title="Create new project">
                        <i class="fas fa-plus"></i>
                        <span>New Project</span>
                    </button>
                </div>
            </div>

            <div class="projects-grid">
                <?php
                function prettifyName($name)
                {
                    global $projectName;
                    $name = str_replace('_', ' ', $name);
                    $name = str_replace('-', ' ', $name);
                    $name = str_replace("/" . $projectName . ' ', '', $name);
                    if (substr($name, -2) == ' 1') {
                        $name = substr($name, 0, -2);
                    }
                    $name = ucwords($name);
                    return $name;
                }

                function gradientFromText($text)
                {
                    $hash = md5($text);
                    $r1 = hexdec(substr($hash, 0, 2));
                    $g1 = hexdec(substr($hash, 2, 2));
                    $b1 = hexdec(substr($hash, 4, 2));
                    $r2 = hexdec(substr($hash, 6, 2));
                    $g2 = hexdec(substr($hash, 8, 2));
                    $b2 = hexdec(substr($hash, 10, 2));
                    return "linear-gradient(135deg, rgb($r1, $g1, $b1), rgb($r2, $g2, $b2))";
                }

                $containers = $Docker->listContainers(true);

                $projects = [];
                $projectPorts = [];
                foreach ($containers as $container) {
                    $containername = $container['Names'][0];
                    $containername = str_replace('-', '_', $containername);
                    $containername = ltrim($containername, '/');

                    $name = explode('_', $containername)[0];
                    if (!isset($projects[$name])) {
                        $projects[$name] = [
                            'containers' => [],
                            'status' => 'running',
                            'children' => 0,
                            'ports' => []
                        ];
                    }
                    $projects[$name]['containers'][] = $container;
                    if ($container['State'] != 'running') {
                        $projects[$name]['status'] = 'stopped';
                    }
                    $projects[$name]['children']++;

                    if (!empty($container['Ports'])) {
                        foreach ($container['Ports'] as $port) {
                            if (isset($port['PublicPort'])) {
                                $projects[$name]['ports'][] = $port['PublicPort'];
                            }
                        }
                    }
                }

                $portCollisions = [];
                foreach ($projects as $name1 => $project1) {
                    foreach ($projects as $name2 => $project2) {
                        if ($name1 !== $name2) {
                            $commonPorts = array_intersect($project1['ports'], $project2['ports']);
                            if (!empty($commonPorts)) {
                                $portCollisions[$name1][$name2] = $commonPorts;
                            }
                        }
                    }
                }

                $logos = [];

                foreach ($projects as $projectName => $project) {
                    $text = strtoupper(substr($projectName, 0, 3));
                    $textFound = false;
                    $i = 0;
                    while (!$textFound && $i < strlen($projectName) - 2) {
                        $text = strtoupper(substr($projectName, $i + ($i * 3), 3));
                        $exists = false;
                        foreach ($logos as $logo) {
                            if (isset($logo['text']) && $logo['text'] === $text) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $textFound = true;
                        } else {
                            $i++;
                        }
                    }
                    $logos[$projectName] = [
                        "text" => $text,
                        "gradient" => gradientFromText($projectName),
                    ];

                    $allPorts = [];
                    foreach ($project['containers'] as $container) {
                        if (!empty($container['Ports'])) {
                            foreach ($container['Ports'] as $port) {
                                if (isset($port['PublicPort'])) {
                                    $allPorts[] = $port['PublicPort'] . '->' . $port['PrivatePort'];
                                } else {
                                    $allPorts[] = $port['PrivatePort'];
                                }
                            }
                        }
                    }
                    $allPorts = array_unique($allPorts);
                    $statuses = array_column($project['containers'], 'State');
                    $uniqueStatuses = array_unique($statuses);
                    $hasMixed = count($uniqueStatuses) > 1;

                    echo "<div class='container-card'>";
                    echo "<div class='container-status-indicator {$project['status']}'></div>";
                    echo "<button class='btn btn-primary container-toggle' onclick='toggleDetails(this)'><i class='fas fa-chevron-down'></i></button>";
                    echo "<div class='container-details'>";

                    echo "<div style='display: flex; align-items: center; margin-bottom: 10px;'>";
                    echo "<div class='container-icon' style='background-image: " . $logos[$projectName]['gradient'] . "; color: white; text-shadow: 0 0 5px rgba(0, 0, 0, 0.5); font-family: \"Orbitron\", sans-serif; font-size: 18px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; overflow: hidden;'>";
                    echo $logos[$projectName]['text'];
                    echo "</div>";
                    echo "<div class='container-name-wrapper'>";
                    echo "<div class='container-name'>{$projectName}</div>";
                    echo "<div class='container-badge {$project['status']}'>{$project['status']}</div>";
                    if ($hasMixed) {
                        echo "<div class='container-badge warning'><i class='fas fa-exclamation-triangle'></i> Mixed</div>";
                    }
                    if (isset($portCollisions[$projectName])) {
                        $conflictingProjects = array_keys($portCollisions[$projectName]);
                        $conflictPorts = implode(', ', reset($portCollisions[$projectName]));
                        echo "<div class='container-badge danger'><i class='fas fa-exclamation-circle'></i> Port conflict with " . implode(', ', $conflictingProjects) . " (Ports: {$conflictPorts})</div>";
                    }
                    echo "</div>";
                    echo "</div>";

                    echo "<div class='container-info'>";
                    echo "<span class='info-item'>";
                    echo "<i class='fas fa-cubes'></i>";
                    echo "<span>Containers: {$project['children']}</span>";
                    echo "</span>";

                    $runningCount = count(array_filter($project['containers'], function ($container) {
                        return $container['State'] === 'running';
                    }));
                    echo "<span class='info-item'>";
                    echo "<i class='fas fa-play-circle'></i>";
                    echo "<span>Running: {$runningCount}/{$project['children']}</span>";
                    echo "</span>";

                    if (!empty($allPorts)) {
                        echo "<span class='info-item'>";
                        echo "<i class='fas fa-network-wired'></i>";
                        echo "<span>Ports: " . count($allPorts) . " exposed</span>";
                        echo "</span>";
                    }
                    echo "</div>";

                    echo "<div class='project-containers'>";
                    foreach ($project['containers'] as $container) {
                        echo "<div class='container-item'>";
                        echo "<span class='container-name' title='{$container['Names'][0]}'>" . basename($container['Names'][0]) . "</span>";
                        echo "<span class='container-image'>" . basename($container['Image']) . "</span>";

                        if (!empty($container['Ports'])) {
                            echo "<div class='container-ports'>";

                            $shownPorts = [];
                            foreach ($container['Ports'] as $port) {
                                if (isset($port['PublicPort']) && !in_array($port['PublicPort'] . '->' . $port['PrivatePort'], $shownPorts)) {
                                    $url = "http://localhost:{$port['PublicPort']}";
                                    echo "<a href='{$url}' target='_blank' class='port-link'>";
                                    echo "<i class='fas fa-external-link-alt'></i> {$port['PrivatePort']}";
                                    echo "</a>";
                                    $shownPorts[] = $port['PublicPort'] . '->' . $port['PrivatePort'];
                                }
                            }
                            echo "</div>";
                        }

                        echo "</div>";
                    }
                    echo "</div>";

                    echo "</div>";

                    echo "<div class='container-actions'>";
                    echo "<a href='/project/index.php?name={$projectName}' class='btn btn-primary'>";
                    echo "<i class='fas fa-cog'></i><span>Manage</span>";
                    echo "</a>";
                    echo "</div>";

                    echo "</div>";
                }
                ?>

            </div>
        </div>
    </main>
    </div>
    <script>
        function toggleDetails(button) {
            const containerCard = button.closest('.container-card');
            const containerDetails = containerCard.querySelector('.container-details');
            const containerGrid = containerCard.querySelector('.projects-grid');

            const icon = button.querySelector('i');

            containerCard.classList.toggle('open');
            containerDetails.classList.toggle('expanded');

            if (containerCard.classList.contains('open')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }


        }
    </script>
</body>

</html>