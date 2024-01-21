<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => ['form-input', 'form-input-image'],
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => basename($field->value() ?? ''),
        'placeholder' => $field->placeholder(),
        'readonly'    => true,
        'required'    => $field->isRequired(),
        'disabled'    => $field->isDisabled(),
        'hidden'      => $field->isHidden(),
    ]) ?>>
    <span class="form-input-reset" data-reset="<?= $field->name() ?>"><?= $this->icon('times-circle') ?></span>
</div>
