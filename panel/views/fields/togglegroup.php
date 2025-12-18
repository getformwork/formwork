<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <fieldset <?= $this->attr([
                    'class'    => 'form-togglegroup',
                    'id'       => $field->name(),
                    'name'     => $field->formName(),
                    'disabled' => $field->isDisabled(),
                    'hidden'   => $field->isHidden(),
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
