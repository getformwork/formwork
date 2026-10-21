<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon')]) ?>
    <select <?= $this->attr([
                'class'            => $this->classes(['form-select', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
        <?php foreach ($field->options() as $value => $label) : ?>
            <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value()]) ?>><?= $this->escape($label) ?></option>
        <?php endforeach ?>
    </select>
</div>
