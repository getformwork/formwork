<?php $this->layout('login') ?>
<div class="caption"><?= $this->translate('register.register') ?></div>
<p><?= $this->translate('register.create-user') ?></p>
<div class="separator"></div>
<form action="<?= $this->uri('/') ?>" method="post">
    <label class="label-required" for="fullname"><?= $this->translate('user.fullname') ?>:</label>
    <input id="fullname" type="text" required name="fullname" autofocus>
    <label class="label-required" for="username"><?= $this->translate('user.username') ?>:</label>
    <span class="label-suggestion">(<?= $this->translate('users.new-user.username-suggestion') ?>)</span>
    <input id="username" type="text" required name="username" pattern="^[a-zA-Z0-9_-]{3,20}$" title="<?= ucfirst($this->translate('users.new-user.username-suggestion')) ?>" maxlength="20" autocomplete="false">
    <label class="label-required" for="password"><?= $this->translate('user.password') ?>:</label>
    <span class="label-suggestion">(<?= $this->translate('users.new-user.password-suggestion') ?>)</span>
    <input id="password" type="password" required name="password" pattern="^.{8,}$" title="<?= ucfirst($this->translate('users.new-user.password-suggestion')) ?>" autocomplete="new-password">
    <label class="label-required" for="email"><?= $this->translate('user.email') ?>:</label>
    <input id="email" type="email" required name="email">
    <label class="label-required" for="email"><?= $this->translate('user.language') ?>:</label>
    <select id="language" name="language">
<?php
    foreach (\Formwork\Admin\Translation::availableLanguages() as $key => $value):
?>
        <option value="<?= $key ?>"<?php if ($key === $admin->translation()->code()): ?> selected<?php endif; ?>><?= $value ?></option>
<?php
    endforeach;
?>
    </select>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <div class="separator"></div>
    <button type="submit" class="button-accent"><?= $this->translate('modal.action.continue') ?></button>
</form>
