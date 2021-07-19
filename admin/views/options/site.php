<?php $this->layout('admin') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('admin.options.options') ?></h3>
    <?= $tabs ?>
    <form method="post" class="options-form" data-form="site-options-form">
        <?php $this->insert('fields', ['fields' => $fields]) ?>
        <div class="separator-l"></div>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        <button type="submit" class="button-accent button-right" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('admin.modal.action.save') ?></button>
    </form>
</div>
