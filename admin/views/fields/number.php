<?= $this->insert('fields.label', ['field' => $field]) ?>
<input <?= $this->attr([
    'type'        => 'number',
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'min'         => $field->get('min'),
    'max'         => $field->get('max'),
    'step'        => $field->get('step'),
    'value'       => $field->value(),
    'placeholder' => $field->placeholder(),
    'required'    => $field->isRequired(),
    'disabled'    => $field->isDisabled()
]) ?>>
