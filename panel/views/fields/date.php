<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'calendar-clock')]) ?>
    <input <?= $this->attr([
                'type'        => 'text',
                'class'       => ['form-input', 'form-input-date'],
                'id'          => $field->name(),
                'name'        => $field->formName(),
                'value'       => $field->toDateTimeString(),
                'placeholder' => $field->placeholder(),
                'required'    => $field->isRequired(),
                'disabled'    => $field->isDisabled(),
                'hidden'      => $field->isHidden(),
                'data-time'   => $field->hasTime() ? 'true' : 'false',
            ]) ?>>
    <span class="form-input-action" data-reset="<?= $field->name() ?>"><?= $this->icon('times-circle') ?></span>
</div>