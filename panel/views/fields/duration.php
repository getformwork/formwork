<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'hourglass')]) ?>
    <input <?= $this->attr([
                'class'          => ['form-input', 'form-input-duration'],
                'type'           => 'number',
                'id'             => $field->name(),
                'name'           => $field->formName(),
                'min'            => $field->get('min'),
                'max'            => $field->get('max'),
                'step'           => $field->get('step'),
                'value'          => $field->value(),
                'required'       => $field->isRequired(),
                'disabled'       => $field->isDisabled(),
                'hidden'         => $field->isHidden(),
                'data-unit'      => $field->get('unit', 'seconds'),
                'data-intervals' => $field->has('intervals') ? implode(', ', $field->get('intervals')) : null,
            ]) ?>>
</div>