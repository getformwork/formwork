<div class="component">
    <h3 class="caption"><?= $this->label('options.options') ?></h3>
    <?= $tabs ?>
    <form method="post" class="options-form" data-form="system-options-form">
        <?= $fields ?>
        <div class="separator-l"></div>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        <button class="button-accent button-save button-right" type="submit" tabindex="4" data-command="save"><i class="i-check"></i> <?= $this->label('modal.action.save') ?></button>
    </form>
</div>
