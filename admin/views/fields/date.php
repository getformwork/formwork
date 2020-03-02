<?= $this->insert('fields.label') ?>
<div class="input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => 'date-input',
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => $field->value(),
        'placeholder' => $field->placeholder(),
        'required'    => $field->get('required'),
        'disabled'    => $field->get('disabled')
    ]) ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
