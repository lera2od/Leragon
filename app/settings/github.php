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
    <title>Leragon - Docker Manager - Settings</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "include/header.php"; ?>


    <main class="main-content">
        <?php include "top.php"; ?>


        <div class="tab-content containers-tab">
            <div class="github-promo">
                <div class="project-overview">
                    <div class="project-header">
                        <div class="project-title-group">
                            <div class="icon-container">
                                <i class="fab fa-github"></i>
                            </div>
                            <h2 class="project-title">Leragon on GitHub</h2>
                        </div>
                    </div>
                    <p class="info-item">
                        Visit our GitHub page to contribute, report issues, or explore the source code
                    </p>
                    <div class="project-containers">
                        <a href="https://github.com/lera2od/Leragon" class="btn btn-primary" target="_blank">
                            <i class="fab fa-github"></i>
                            View on GitHub
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>