<?php $this->layout('panel') ?>

<?php $this->modals()->add('changes') ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon('gear') ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->translate('panel.options.options') ?></div>
        </div>
    </div>

    <div class="panel-header-actions">
        <button type="submit" class="button button-accent" form="site-options-form" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
    </div>
</header>

<form method="post" id="site-options-form" enctype="multipart/form-data" class="options-form" data-form="site-options-form">
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?= $tabs ?>
    <div>
        <?php $this->insert('fields', ['fields' => $fields]) ?>
    </div>
</form>