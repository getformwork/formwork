<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'        => 'text',
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'value'       => $field->value(),
    'placeholder' => $field->placeholder(),
    'required'    => $field->isRequired(),
    'disabled'    => $field->isDisabled()
]) ?>>
