<?= $this->insert('fields.label', ['field' => $field]) ?>
<input <?= $this->attr([
    'type'     => 'range',
    'id'       => $field->name(),
    'name'     => $field->formName(),
    'min'      => $field->get('min'),
    'max'      => $field->get('max'),
    'step'     => $field->get('step'),
    'value'    => $field->value(),
    'required' => $field->isRequired(),
    'disabled' => $field->isDisabled()
]) ?>>
<output class="input-range-value" for="<?= $field->name() ?>"><?= $field->value() ?></output>
