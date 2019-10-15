<?= $this->insert('fields.label') ?>
<div class="input-wrap">
    <input class="image-input" type="text" id="<?= $field->name() ?>" name="<?= $field->formName() ?>" value="<?= basename($field->value()) ?>" placeholder="<?= $field->placeholder() ?>" readonly>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
