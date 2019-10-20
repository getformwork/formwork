<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="base-uri" content="<?= $this->panelUri() ?>">
    <link rel="shortcut icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri('css/admin.min.css', true) ?>">
</head>
<body>
    <main>
        <div class="container-full">
            <div class="login-modal-container">
            <?php if ($notification = $this->notification()): ?>
                <div class="login-modal-<?= $notification['type'] ?>"><?= $notification['text'] ?></div>
            <?php endif; ?>
                <div class="caption"><?= $this->label('login.login') ?></div>
                <form action="<?= $this->uri('/login/') ?>" method="post">
                    <label for="username"><?= $this->label('login.username') ?>:</label>
                    <input id="username" type="text" required name="username" <?php if (!empty($username)): ?>value="<?= $username ?>"<?php else: ?>autofocus<?php endif; ?> maxlength="20">
                    <label for="password"><?= $this->label('login.password') ?>:</label>
                    <input <?php if (!empty($error)): ?>class="input-invalid" autofocus <?php endif; ?>id="password" type="password" required name="password">
                    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                    <div class="separator"></div>
                    <button type="submit"><?= $this->label('login.login') ?></button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
