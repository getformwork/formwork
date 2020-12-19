<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
<?php
    if (isset($csrfToken)):
?>
    <meta name="csrf-token" content="<?= $csrfToken ?>">
<?php
    endif;
?>
<?php
    if ($notification = $admin->notification()):
?>
    <meta name="notification" content="<?= $notification['text']?>" data-type="<?= $notification['type']?>" data-interval="5000">
<?php
    endif;
?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/admin-dark.min.css' : 'css/admin.min.css', true) ?>">
</head>
<body>
    <?php $this->insert('partials.sidebar') ?>
    <div class="title-bar">
        <span class="panel-title"><?= $this->translate('admin.panel') ?></span>
        <a href="<?= $admin->siteUri() ?>" class="view-site" target="formwork-view-site"><span class="show-from-xs"><?= $this->translate('admin.view-site') ?></span> <i class="i-external-link-square"></i></a>
    </div>
    <main class="main">
        <?= $this->content() ?>
    </main>
    <?= $modals ?>
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>"></script>
    <script>Formwork.config = <?= $appConfig ?>;</script>
</body>
</html>
