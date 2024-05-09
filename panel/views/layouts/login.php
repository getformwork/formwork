<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/panel-dark.min.css' : 'css/panel.min.css', true) ?>">
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