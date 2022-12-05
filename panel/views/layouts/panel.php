<!DOCTYPE html>
<html lang="<?= $formwork->translations()->getCurrent()->code() ?>">
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork</title>
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
    if ($notification = $panel->notification()):
?>
    <meta name="notification" content='<?= $this->escapeAttr(Formwork\Parsers\JSON::encode([$notification])) ?>'>
<?php
    endif;
?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/panel-dark.min.css' : 'css/panel.min.css', true) ?>">
</head>
<body>
    <?php $this->insert('partials.sidebar') ?>
    <div class="title-bar">
        <span class="panel-title"><?= $this->translate('panel.panel') ?></span>
        <a href="<?= $site->uri() ?>" class="view-site" target="formwork-view-site"><span class="show-from-xs"><?= $this->translate('panel.view-site') ?></span> <?= $this->icon('arrow-right-up-box') ?></a>
    </div>
    <main class="main">
        <div class="container">
            <?= $this->content() ?>
        </div>
    </main>
    <?= $modals ?>
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>" integrity="<?= $this->assets()->integrityHash('js/app.min.js') ?>"></script>
    <script>Formwork.config = <?= $appConfig ?>;</script>
</body>
</html>
