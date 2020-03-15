<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'     => 'range',
    'id'       => $field->name(),
    'name'     => $field->formName(),
    'min'      => $field->get('min'),
    'max'      => $field->get('max'),
    'step'     => $field->get('step'),
    'value'    => $field->value(),
    'required' => $field->get('required'),
    'disabled' => $field->get('disabled')
]) ?>>
<span class="range-input-value"><?= $field->value() ?></span>
