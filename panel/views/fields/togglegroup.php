<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <fieldset <?= $this->attr([
                    'id'       => $field->name(),
                    'class'    => 'form-togglegroup',
                    'disabled' => $field->isDisabled(),
                    'hidden'   => $field->isHidden(),
                ]) ?>>
        <?php foreach ((array) $field->get('options') as $value => $label) : ?>
            <label class="form-label">
                <input <?= $this->attr([
                            'class'   => 'form-input',
                            'type'    => 'radio',
                            'name'    => $field->formName(),
                            'value'   => $value,
                            'checked' => $value == $field->value(),
                        ]) ?>>
                <span><?= $label ?></span>
            </label>
        <?php endforeach ?>
    </fieldset>
</div>