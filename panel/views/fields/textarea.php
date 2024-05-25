<?php $this->layout('fields.field') ?>
<textarea <?= $this->attr([
                'class'       => 'form-textarea',
                'id'          => $field->name(),
                'name'        => $field->formName(),
                'placeholder' => $field->placeholder(),
                'rows'        => $field->get('rows', 5),
                'required'    => $field->isRequired(),
                'disabled'    => $field->isDisabled(),
                'hidden'      => $field->isHidden(),
            ]) ?>><?= $this->escape((string) $field->value()) ?></textarea>