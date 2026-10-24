<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <fieldset <?= $this->attr([
                    'class'            => $this->classes(['form-togglegroup', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                    'id'               => $field->name(),
                    'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                    'name'             => $field->formName(),
                    'disabled'         => $field->isDisabled(),
                    'hidden'           => $field->isHidden(),
                ]) ?>>
        <?php foreach ((array) $field->options() as $value => $label) : ?>
            <label class="form-label">
                <input <?= $this->attr([
                            'class'   => 'form-input',
                            'type'    => 'radio',
                            'name'    => $field->formName(),
                            'value'   => $value,
                            'checked' => $value == $field->value(),
                        ]) ?>>
                <span><?= $this->escape($label) ?></span>
            </label>
        <?php endforeach ?>
    </fieldset>
</div>
