<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>" class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="<?= $panel->colorScheme()->getCompatibleSchemes() ?>">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('images/icon.png')->uri() ?>">
    <?php $this->assets()->add('css/panel.min.css') ?>
    <?php $this->insert('partials.stylesheets') ?>
</head>

<body>
    <main>
        <div class="container-full">
            <div class="<?= $this->classes(['login-container', 'form-input-invalid' => $error ?? false]) ?>">
                <div class="sections">
                    <section class="section">
                        <?= $this->insert('_login/notification') ?>
                        <?= $this->content() ?>
                    </section>
                </div>
            </div>
        </div>
    </main>
    <?php $this->assets()->add('js/app.min.js', ['module' => true]) ?>
    <?php $this->insert('partials.scripts') ?>
</body>

</html>