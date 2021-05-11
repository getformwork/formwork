<?= $this->insert('fields.label', ['field' => $field]) ?>
<input <?= $this->attr([
    'type'         => 'text',
    'id'           => $field->name(),
    'name'         => $field->formName(),
    'value'        => implode(', ', (array) $field->value()),
    'placeholder'  => $field->placeholder(),
    'required'     => $field->isRequired(),
    'disabled'     => $field->isDisabled(),
    'data-field'   => 'tags',
    'data-options' => $field->has('options') ? Formwork\Parsers\JSON::encode((array) $field->get('options')) : null
]) ?>>
