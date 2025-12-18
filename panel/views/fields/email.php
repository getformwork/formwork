<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'envelope')]) ?>
    <input <?= $this->attr([
                'class'        => 'form-input',
                'type'         => 'email',
                'id'           => $field->name(),
                'name'         => $field->formName(),
                'value'        => $field->value(),
                'placeholder'  => $field->placeholder(),
                'minlength'    => $field->minLength(),
                'maxlength'    => $field->maxLength(),
                'pattern'      => $field->pattern(),
                'autocomplete' => $field->autocomplete(),
                'required'     => $field->isRequired(),
                'disabled'     => $field->isDisabled(),
                'hidden'       => $field->isHidden(),
            ]) ?>>
</div>