<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'         => 'number',
    'id'           => $field->name(),
    'name'         => $field->formName(),
    'min'          => $field->get('min'),
    'max'          => $field->get('max'),
    'step'         => $field->get('step'),
    'value'        => $field->value(),
    'required'     => $field->isRequired(),
    'disabled'     => $field->isDisabled(),
    'data-field'   => 'duration',
    'data-display' => $field->has('display') ? implode(', ', $field->get('display')) : null,
    'data-unit'    => $field->get('unit', 'seconds')
]) ?>>
