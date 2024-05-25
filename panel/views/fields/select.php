<?php $this->layout('fields.field') ?>
<select <?= $this->attr([
            'class'    => 'form-select',
            'id'       => $field->name(),
            'name'     => $field->formName(),
            'required' => $field->isRequired(),
            'disabled' => $field->isDisabled(),
            'hidden'   => $field->isHidden(),
        ]) ?>>
    <?php foreach ($field->options() as $value => $label) : ?>
        <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value()]) ?>><?= $this->escape($label) ?></option>
    <?php endforeach ?>
</select>