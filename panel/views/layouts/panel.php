<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>" class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?php foreach ($panel->notifications() as $notification) : ?>
        <meta name="notification" content='<?= $this->escapeAttr(Formwork\Parsers\Json::encode($notification)) ?>'>
    <?php endforeach ?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('images/icon.png')->uri() ?>">
    <?php $this->assets()->add('css/panel.min.css') ?>
    <?php $this->insert('partials.stylesheets') ?>
</head>

<body>
    <?php $this->insert('partials.sidebar') ?>
    <main class="panel-main">
        <div class="container">
            <?= $this->content() ?>
        </div>
    </main>
    <?php foreach ($this->modals() as $modal) : ?>
        <?php $this->insert('modals.modal', ['modal' => $modal]) ?>
    <?php endforeach ?>
    <?php $this->assets()->add('js/app.min.js', ['module' => true]) ?>
    <?php $this->insert('partials.scripts') ?>
</body>

</html>