<?= $this->insert('fields.label') ?>
<div class="input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => 'image-input',
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => basename($field->value()),
        'placeholder' => $field->placeholder(),
        'readonly'    => true
    ]) ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
