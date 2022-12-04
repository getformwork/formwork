<?= $this->layout('fields.field') ?>
<select <?= $this->attr([
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'required'    => $field->isRequired(),
    'disabled'    => $field->isDisabled(),
    'hidden'      => $field->isHidden()
]) ?>>
<?php foreach ((array) $field->get('options') as $value => $label): ?>
    <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value()]) ?>><?= $label ?></option>
<?php endforeach; ?>
</select>
