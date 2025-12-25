<?php $this->layout('@panel.login') ?>
<div class="section-header">
    <div class="caption"><?= $this->translate('panel.register.register') ?></div>
</div>
<p class="mb-8"><?= $this->translate('panel.register.createUser') ?></p>
<form action="<?= $panel->uri('/register/') ?>" method="post" data-form="register-form">
    <?php foreach ($fields as $field) : ?>
        <?php $this->insert("@panel.fields.{$field->type()}", ['field' => $field]) ?>
    <?php endforeach ?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <button type="submit" class="button button-accent mt-8"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
</form>