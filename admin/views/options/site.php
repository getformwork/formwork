<div class="component">
    <h3 class="caption"><?= $this->label('options.options') ?></h3>
    <?= $tabs ?>
    <form class="site-options-form" method="post">
        <?= $fields ?>
        <div class="separator-l"></div>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        <button class="button-accent button-save button-right" type="submit" tabindex="4"><i class="i-check"></i> <?= $this->label('modal.action.save') ?></button>
    </form>
</div>
<script>
new Form('.site-options-form');
</script>
