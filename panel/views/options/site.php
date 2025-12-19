<?php $this->layout('@panel.panel') ?>

<?php $this->modals()->add('changes') ?>

<form method="post" enctype="multipart/form-data" class="options-form" data-form="site-options-form">
    <header class="panel-header">
        <div class="header-icon"><?= $this->icon('gear') ?></div>
        <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
        <div>
            <button type="submit" class="button button-accent button-right" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        </div>
    </header>
    <?= $tabs ?>
    <div>
        <?php $this->insert('@panel.fields', ['fields' => $fields]) ?>
    </div>
</form>
