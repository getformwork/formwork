<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'image')]) ?>
    <select <?= $this->attr([
                'class'    => ['form-select', 'form-image'],
                'id'       => $field->name(),
                'name'     => $field->formName(),
                'required' => $field->isRequired(),
                'disabled' => $field->isDisabled(),
                'hidden'   => $field->isHidden(),
            ]) ?>>
        <?php if (!$field->isRequired()): ?>
            <option value=""><?= $this->translate('fields.image.none') ?></option>
        <?php endif ?>
        <?php foreach ($field->options() as $value => $label) : ?>
            <option <?= $this->attr(['value' => $value, 'selected' => $value == $field->value(), 'data-icon' => $label['icon'], 'data-thumb' => $label['thumb']]) ?>><?= $this->escape($label['value']) ?></option>
        <?php endforeach ?>
    </select>
</div>