<?php $this->layout('login') ?>
<div class="caption"><?= $this->translate('panel.login.login') ?></div>
<form action="<?= $panel->uri('/login/') ?>" method="post">
    <label class="form-label" for="username"><?= $this->translate('panel.login.username') ?>:</label>
    <input class="form-input" id="username" type="text" required name="username" <?php if (!empty($username)): ?>value="<?= $username ?>" <?php else: ?>autofocus<?php endif ?> maxlength="20">
    <label class="form-label" for="password"><?= $this->translate('panel.login.password') ?>:</label>
    <input class="form-input" <?php if (!empty($error)): ?>class="form-input-invalid" autofocus <?php endif ?>id="password" type="password" required name="password">
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <button type="submit" class="button button-accent mt-8"><?= $this->icon('arrow-right-circle') ?> <?= $this->translate('panel.login.login') ?></button>
</form>
