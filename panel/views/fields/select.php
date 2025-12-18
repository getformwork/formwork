<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon')]) ?>
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
</div>