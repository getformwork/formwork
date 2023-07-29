<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/panel-dark.min.css' : 'css/panel.min.css', true) ?>">
</head>
<body>
    <main>
        <div class="container-full">
            <div class="error-container">
                <h1>
                    <span class="error-code"><?= $code ?></span>
                    <span class="error-status"><?= $status ?></span>
                </h1>
                <div class="logo" style="background-image: url(<?= $this->assets()->uri('images/icon.svg') ?>);"></div>
                <h2><?= $heading ?></h2>
                <p><?= $description ?></p>
                <?php if (isset($action)): ?><a class="action" href="<?= $action['href'] ?>"><?= $action['label'] ?></a><?php endif ?>
            </div>
        </div>
    </main>
</body>
</html>
