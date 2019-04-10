<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
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
                <div class="caption"><?= $this->label('register.register') ?></div>
                <p><?= $this->label('register.create-user') ?></p>
                <div class="separator"></div>
                <form action="<?= $this->uri('/') ?>" method="post">
                    <label class="label-required" for="fullname"><?= $this->label('user.fullname') ?>:</label>
                    <input id="fullname" type="text" required name="fullname" autofocus>
                    <label class="label-required" for="username"><?= $this->label('user.username') ?>:</label>
                    <span class="label-suggestion">(<?= $this->label('users.new-user.username-suggestion') ?>)</span>
                    <input id="username" type="text" required name="username" pattern="^[a-zA-Z0-9_-]{3,20}$" title="<?= ucfirst($this->label('users.new-user.username-suggestion')) ?>" maxlength="20" autocomplete="false">
                    <label class="label-required" for="password"><?= $this->label('user.password') ?>:</label>
                    <span class="label-suggestion">(<?= $this->label('users.new-user.password-suggestion') ?>)</span>
                    <input id="password" type="password" required name="password" pattern="^.{8,}$" title="<?= ucfirst($this->label('users.new-user.password-suggestion')) ?>" autocomplete="new-password">
                    <label class="label-required" for="email"><?= $this->label('user.email') ?>:</label>
                    <input id="email" type="email" required name="email">
                    <label class="label-required" for="email"><?= $this->label('user.language') ?>:</label>
                    <select id="language" name="language">
<?php
                    foreach ($this->languages() as $key => $value):
?>
                        <option value="<?= $key ?>"<?php if ($key === $this->language()): ?> selected<?php endif; ?>><?= $value ?></option>
<?php
                    endforeach;
?>
                    </select>
                    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                    <div class="separator"></div>
                    <button class="button-accent" type="submit"><?= $this->label('modal.action.continue') ?></button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
