<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'file')]) ?>
    <select <?= $this->attr([
                'class'            => $this->classes(['form-select', 'form-file', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
        <?php if (!$field->isRequired()): ?>
            <option value=""><?= $this->translate('fields.file.none') ?></option>
        <?php endif ?>
        <?php foreach ($field->options() as $value => $label) : ?>
            <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value(), 'data-icon' => $label['icon'], 'data-thumb' => $label['thumb']]) ?>><?= $this->escape($label['value']) ?></option>
        <?php endforeach ?>
    </select>
</div>
