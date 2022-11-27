<?= $this->layout('fields.field') ?>
<div <?= $this->attr([
    'class'     => ['input-array', $field->get('associative') ? 'input-array-associative' : ''],
    'id'        => $field->name(),
    'hidden'    => $field->isHidden(),
    'data-name' => $field->formName()
]) ?>>
<?php foreach ($field->value() ?: ['' => ''] as $key => $value): ?>
    <div class="input-array-row">
        <span class="sort-handle" title="<?= $this->translate('admin.drag-to-reorder') ?>"><?= $this->icon('grabber') ?></span>
        <?php if ($field->get('associative')): ?>
        <input <?= $this->attr([
            'type'        => 'text',
            'class'       => 'input-array-key',
            'value'       => $key,
            'placeholder' => $field->get('placeholder_key')
        ]) ?>>
        <?php endif; ?>
        <input <?= $this->attr([
            'type'        => 'text',
            'class'       => 'input-array-value',
            'name'        => $field->formName() . ($field->get('associative') ? '[' . $key . ']' : '[]'),
            'value'       => $value,
            'placeholder' => $field->get('placeholder_value')
        ]) ?>>
        <button type="button" class="button-link input-array-remove" title="<?= $this->translate('fields.array.remove') ?>" aria-label="<?= $this->translate('fields.array.remove') ?>"><?= $this->icon('minus-circle') ?></button>
        <button type="button" class="button-link input-array-add" title="<?= $this->translate('fields.array.add') ?>" aria-label="<?= $this->translate('fields.array.add') ?>"><?= $this->icon('plus-circle') ?></button>
    </div>
<?php endforeach; ?>
</div>
