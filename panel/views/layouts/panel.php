<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>" class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?php foreach ($panel->notifications() as $notification) : ?>
        <meta name="notification" content='<?= $this->escapeAttr(Formwork\Parsers\Json::encode($notification)) ?>'>
    <?php endforeach ?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('@panel/images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('@panel/images/icon.png')->uri() ?>">
    <?php $this->assets()->add('@panel/css/panel.min.css') ?>
    <?php $this->insert('@panel._stylesheets') ?>
</head>

<body>
    <?php $this->insert('@panel._sidebar') ?>
    <header class="panel-header">
        <span class="show-from-sm"><?= $this->translate('panel.panel') ?></span>
        <span class="show-from-sm ml-5 mr-2 text-color-gray-medium">/</span>
        <?php if ($panel->user()->permissions()->has('panel.options.site')) : ?>
            <span class="flex-grow-1"><a class="button button-link" href="<?= $panel->uri('/options/site/') ?>"><?= $this->icon('globe') ?> <span class="ml-2"><?= $this->escape($site->title()) ?></span></a></span>
        <?php else : ?>
            <span class="flex-grow-1"><span class="pl-4"><?= $this->icon('globe') ?> <span class="ml-2"><?= $this->escape($site->title()) ?></span></span></span>
        <?php endif ?>
        <a href="<?= $site->uri() ?>" class="button button-link" target="formwork-view-site"><span class="show-from-xs"><?= $this->translate('panel.viewSite') ?></span> <?= $this->icon('arrow-right-up-box') ?></a>
    </header>
    <main class="panel-main">
        <div class="container">
            <?= $this->content() ?>
        </div>
    </main>
    <?php foreach ($this->modals() as $modal) : ?>
        <?php $this->insert('@panel.modals.modal', ['modal' => $modal]) ?>
    <?php endforeach ?>
    <?php $this->assets()->add('@panel/js/app.min.js', ['module' => true]) ?>
    <?php $this->insert('@panel._scripts') ?>
</body>

</html>