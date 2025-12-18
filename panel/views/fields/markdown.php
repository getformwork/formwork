<?php $this->layout('fields.field') ?>

<?php $this->modals()->addMultiple(['images', 'link']) ?>

<textarea <?= $this->attr([
                'class'         => ['form-textarea', 'editor-textarea', 'is-loading'],
                'id'            => $field->name(),
                'name'          => $field->formName(),
                'placeholder'   => $field->placeholder(),
                'minlength'     => $field->minLength(),
                'maxlength'     => $field->maxLength(),
                'autocomplete'  => $field->autocomplete() ? 'on' : 'off',
                'spellcheck'    => $field->spellcheck() ? 'true' : 'false',
                'rows'          => $field->rows(),
                'required'      => $field->isRequired(),
                'disabled'      => $field->isDisabled(),
                'hidden'        => $field->isHidden(),
                'data-base-uri' => $field?->parent()?->model()?->uri(),
            ]) ?>><?= $this->escape($field->value() ?? '') ?></textarea>