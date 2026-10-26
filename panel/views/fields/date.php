<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'calendar-clock')]) ?>
    <input <?= $this->attr([
                'type'             => 'text',
                'class'            => $this->classes(['form-input', 'form-input-date', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'value'            => $field->toDateTimeString(),
                'placeholder'      => $field->placeholder(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
                'data-time'        => $field->hasTime() ? 'true' : 'false',
            ]) ?>>
    <button type="button" class="form-input-action" data-reset="<?= $field->name() ?>" title="<?= $this->translate('date.noDate') ?>" aria-label="<?= $this->translate('date.noDate') ?>"><?= $this->icon('times-circle') ?></button>
</div>