<!DOCTYPE html>
<html lang="<?= $formwork->translations()->getCurrent()->code() ?>">
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/admin-dark.min.css' : 'css/admin.min.css', true) ?>">
</head>
<body>
    <main>
        <div class="container-full">
            <div class="login-modal-container">
            <?php if ($notification = $admin->notification()): ?>
                <div class="login-modal-<?= $notification['type'] ?>"><?= $notification['text'] ?></div>
            <?php endif; ?>
                <?= $this->content() ?>
            </div>
        </div>
    </main>
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>"></script>
    <script>Formwork.config = <?= $appConfig ?>;</script>
</body>
</html>
