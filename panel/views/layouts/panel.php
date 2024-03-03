<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?php if (isset($csrfToken)) : ?>
        <meta name="csrf-token" content="<?= $csrfToken ?>">
    <?php endif ?>
    <?php foreach ($panel->notifications() as $notification) : ?>
        <meta name="notification" content='<?= $this->escapeAttr(Formwork\Parsers\Json::encode($notification)) ?>'>
    <?php endforeach ?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/panel-dark.min.css' : 'css/panel.min.css', true) ?>">
</head>

<body>
    <?php $this->insert('partials.sidebar') ?>
    <header class="panel-header">
        <span class="flex-grow-1"><?= $this->translate('panel.panel') ?></span>
        <a href="<?= $site->uri() ?>" class="button button-link text-size-md" target="formwork-view-site"><span class="show-from-xs"><?= $this->translate('panel.viewSite') ?></span> <?= $this->icon('arrow-right-up-box') ?></a>
    </header>
    <main class="panel-main">
        <div class="container">
            <?= $this->content() ?>
        </div>
    </main>
    <?= $modals ?>
    <?php $this->insert('partials.scripts') ?>
</body>

</html>