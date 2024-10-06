<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>" class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="<?= $panel->colorScheme()->getCompatibleSchemes() ?>">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri('css/panel.min.css', true) ?>">
</head>

<body>
    <main>
        <div class="container-full">
            <div class="login-modal-container">
                <?= $this->insert('_login/notification') ?>
                <?= $this->content() ?>
            </div>
        </div>
    </main>
    <?php $this->insert('partials.scripts') ?>
</body>

</html>