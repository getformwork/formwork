<?php $this->layout('panel') ?>


<form method="post" class="options-form" data-form="system-options-form">
    <div class="header">
        <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
        <div>
            <button type="submit" class="button-accent button-right" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        </div>
    </div>
    <?= $tabs ?>
    <div class="component">
        <?php $this->insert('fields', ['fields' => $fields]) ?>
    </div>
</form>

