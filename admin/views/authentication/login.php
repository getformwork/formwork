<?php $this->layout('login') ?>
<div class="caption"><?= $this->translate('login.login') ?></div>
<form action="<?= $this->uri('/login/') ?>" method="post">
    <label for="username"><?= $this->translate('login.username') ?>:</label>
    <input id="username" type="text" required name="username" <?php if (!empty($username)): ?>value="<?= $username ?>"<?php else: ?>autofocus<?php endif; ?> maxlength="20">
    <label for="password"><?= $this->translate('login.password') ?>:</label>
    <input <?php if (!empty($error)): ?>class="input-invalid" autofocus <?php endif; ?>id="password" type="password" required name="password">
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <div class="separator"></div>
    <button type="submit"><?= $this->translate('login.login') ?></button>
</form>
