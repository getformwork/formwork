<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'files')]) ?>
    <input <?= $this->attr([
                'class'          => ['form-input', 'form-input-tags', 'form-files'],
                'type'           => 'text',
                'id'             => $field->name(),
                'name'           => $field->formName(),
                'value'          => implode(', ', (array) $field->value()),
                'placeholder'    => $field->placeholder(),
                'required'       => $field->isRequired(),
                'disabled'       => $field->isDisabled(),
                'hidden'         => $field->isHidden(),
                'data-limit'     => $field->limit(),
                'data-options'   => Formwork\Parsers\Json::encode($field->options()),
                'data-accept'    => 'options',
                'data-orderable' => $field->isOrderable(),
            ]) ?>>
</div>