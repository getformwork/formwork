<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'         => 'text',
    'id'           => $field->name(),
    'name'         => $field->formName(),
    'value'        => implode(', ', (array) $field->value()),
    'placeholder'  => $field->placeholder(),
    'required'     => $field->isRequired(),
    'disabled'     => $field->isDisabled(),
    'data-field'   => 'tags',
    'data-options' => $field->has('options') ? $this->escape(json_encode((array) $field->get('options'))) : null
]) ?>>
