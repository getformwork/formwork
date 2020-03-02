<?= $this->insert('fields.label') ?>
<select <?= $this->attr([
    'id'       => $field->name(),
    'name'     => $field->formName(),
    'required' => $field->get('required'),
    'disabled' => $field->get('disabled')
]) ?>>
<?php foreach ((array) $field->get('options') as $value => $label): ?>
    <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value()]) ?>><?= $label ?></option>
<?php endforeach; ?>
</select>
