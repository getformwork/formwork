<!DOCTYPE html>
<html lang="<?= $formwork->translations()->getCurrent()->code() ?>">
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/panel-dark.min.css' : 'css/panel.min.css', true) ?>">
</head>
<body>
    <main>
        <div class="container-full">
            <div class="login-modal-container">
            <?php if ($notification = $panel->notification()): ?>
                <div class="login-modal-<?= $notification['type'] ?>"><?= $this->icon($notification['icon']) ?> <?= $notification['text'] ?></div>
            <?php endif; ?>
                <?= $this->content() ?>
            </div>
        </div>
    </main>
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>" integrity="<?= $this->assets()->integrityHash('js/app.min.js') ?>"></script>
    <script>Formwork.config = <?= $appConfig ?>;</script>
</body>
</html>
