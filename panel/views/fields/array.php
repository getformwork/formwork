<?php $this->layout('fields.field') ?>
<div <?= $this->attr([
            'class'     => ['form-input-array', $field->get('associative') ? 'form-input-array-associative' : ''],
            'id'        => $field->name(),
            'hidden'    => $field->isHidden(),
            'data-name' => $field->formName(),
        ]) ?>>
    <?php foreach ($field->value() ?: ['' => ''] as $key => $value) : ?>
        <div class="form-input-array-row">
            <span class="sortable-handle" title="<?= $this->translate('panel.dragToReorder') ?>"><?= $this->icon('grabber') ?></span>
            <?php if ($field->get('associative')) : ?>
                <input <?= $this->attr([
                            'type'        => 'text',
                            'class'       => ['form-input', 'form-input-array-key'],
                            'value'       => $key,
                            'placeholder' => $field->get('placeholderKey'),
                        ]) ?>>
            <?php endif ?>
            <input <?= $this->attr([
                        'type'        => 'text',
                        'class'       => ['form-input', 'form-input-array-value'],
                        'name'        => $field->formName() . ($field->get('associative') ? '[' . $key . ']' : '[]'),
                        'value'       => $value,
                        'placeholder' => $field->get('placeholderValue'),
                    ]) ?>>
            <button type="button" class="button button-link form-input-array-remove" title="<?= $this->translate('fields.array.remove') ?>" aria-label="<?= $this->translate('fields.array.remove') ?>"><?= $this->icon('minus-circle') ?></button>
            <button type="button" class="button button-link form-input-array-add" title="<?= $this->translate('fields.array.add') ?>" aria-label="<?= $this->translate('fields.array.add') ?>"><?= $this->icon('plus-circle') ?></button>
        </div>
    <?php endforeach ?>
</div>