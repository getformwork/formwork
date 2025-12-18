<?php $this->layout('fields.field') ?>
<textarea <?= $this->attr([
                'class'        => 'form-textarea',
                'id'           => $field->name(),
                'name'         => $field->formName(),
                'placeholder'  => $field->placeholder(),
                'minlength'    => $field->minLength(),
                'maxlength'    => $field->maxLength(),
                'autocomplete' => $field->autocomplete() ? 'on' : 'off',
                'spellcheck'   => $field->spellcheck() ? 'true' : 'false',
                'rows'         => $field->rows(),
                'required'     => $field->isRequired(),
                'disabled'     => $field->isDisabled(),
                'hidden'       => $field->isHidden(),
            ]) ?>><?= $this->escape((string) $field->value()) ?></textarea>