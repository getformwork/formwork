<?php $this->layout('login') ?>
<div class="caption"><?= $this->translate('panel.register.register') ?></div>
<p><?= $this->translate('panel.register.createUser') ?></p>
<div class="separator"></div>
<form action="<?= $panel->uri('/register/') ?>" method="post">
    <label class="label-required" for="fullname"><?= $this->translate('panel.user.fullname') ?>:</label>
    <input id="fullname" type="text" required name="fullname" autofocus>
    <label class="label-required" for="username"><?= $this->translate('panel.user.username') ?>:</label>
    <span class="label-suggestion">(<?= $this->translate('panel.users.newUser') ?>)</span>
    <input id="username" type="text" required name="username" pattern="^[a-zA-Z0-9_-]{3,20}$" title="<?= ucfirst($this->translate('panel.users.newUser')) ?>" maxlength="20" autocomplete="false">
    <label class="label-required" for="password"><?= $this->translate('panel.user.password') ?>:</label>
    <span class="label-suggestion">(<?= $this->translate('panel.users.newUser') ?>)</span>
    <input id="password" type="password" required name="password" pattern="^.{8,}$" title="<?= ucfirst($this->translate('panel.users.newUser')) ?>" autocomplete="new-password">
    <label class="label-required" for="email"><?= $this->translate('panel.user.email') ?>:</label>
    <input id="email" type="email" required name="email">
    <label class="label-required" for="email"><?= $this->translate('panel.user.language') ?>:</label>
    <select id="language" name="language">
<?php
    foreach ($panel->availableTranslations() as $key => $value):
        ?>
        <option value="<?= $key ?>"<?php if ($key === $app->config()->get('system.panel.translation')): ?> selected<?php endif ?>><?= $value ?></option>
<?php
    endforeach
?>
    </select>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <div class="separator"></div>
    <button type="submit" class="button-accent"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
</form>
