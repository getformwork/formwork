<?php $this->layout('login') ?>
<div class="section-header">
    <div class="caption"><?= $this->translate('panel.login.login') ?></div>
</div>
<form action="<?= $panel->uri('/login/') ?>" method="post">
    <?php foreach ($fields as $field) : ?>
        <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
    <?php endforeach ?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <button type="submit" class="button button-accent mt-8"><?= $this->icon('arrow-right-circle') ?> <?= $this->translate('panel.login.login') ?></button>
</form>
