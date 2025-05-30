<?php include "include/projectHandler.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leragon - <?= htmlspecialchars(ucfirst($projectName)); ?> - Images</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>
    <?php include "include/header.php"; ?>

    <main class="main-content">
        <?php include "top.php"; ?>
        <div class="tab-content images-tab">
            <div class="container-list">
                <?php
                $Docker = new DockerManager();
                $allImages = $Docker->listImages(true);
                $images = [];

                foreach ($projectDetails['containers'] as $container) {
                    $imageName = $container['image'];
                    $image = array_filter($allImages, function ($img) use ($imageName) {
                        return strpos($img['RepoTags'][0], $imageName) !== false;
                    });
                    $image = array_values($image);
                    if (empty($image[0])) {
                        continue;
                    }
                    $images[] = $image[0];
                }

                foreach ($images as $image):
                    $tags = $image['RepoTags'] ?? ['<none>:<none>'];
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
    </div>
    <script>
        async function removeImage(imageId) {
            if (!await confirmModal('Are you sure you want to remove this image?')) return;

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
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    toast.show('Failed to remove image: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toast.show('Error removing image', 'error');
            }
        }
    </script>
</body>

</html>