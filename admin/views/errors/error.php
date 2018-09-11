<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= $this->uri('/assets/images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->uri('/assets/css/admin.min.css') ?>">
</head>
<body>
    <main>
        <div class="container-full">
            <div class="error-container">
                <h1>
                    <span class="error-code"><?= $code ?></span>
                    <span class="error-status"><?= $status ?></span>
                </h1>
                <div class="logo" style="background-image: url(<?= $this->uri('/assets/images/icon.png') ?>);"></div>
                <h2><?= $heading ?></h2>
                <p><?= $description ?></p>
                <?php if (isset($action)): ?><a class="action" href="<?= $action['href'] ?>"><?= $action['label'] ?></a><?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
