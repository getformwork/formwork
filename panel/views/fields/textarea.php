<?php $this->layout('@panel.fields.field') ?>
<textarea <?= $this->attr([
                'class'            => $this->classes(['form-textarea', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'placeholder'      => $field->placeholder(),
                'minlength'        => $field->minLength(),
                'maxlength'        => $field->maxLength(),
                'autocomplete'     => $field->autocomplete() ? 'on' : 'off',
                'spellcheck'       => $field->spellcheck() ? 'true' : 'false',
                'rows'             => $field->rows(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>><?= $this->escape((string) $field->value()) ?></textarea>
