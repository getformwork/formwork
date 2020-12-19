<?= $this->insert('fields.label') ?>
<div class="input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => 'date-input',
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => $field->value(),
        'placeholder' => $field->placeholder(),
        'required'    => $field->isRequired(),
        'disabled'    => $field->isDisabled()
    ]) ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
