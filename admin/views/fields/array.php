<?= $this->insert('fields.label') ?>
<div <?= $this->attr([
    'class'     => ['array-input', $field->get('associative') ? 'array-input-associative' : ''],
    'id'        => $field->name(),
    'data-name' => $field->formName()
]) ?>>
<?php foreach ($field->value() ?: ['' => ''] as $key => $value): ?>
    <div class="array-input-row">
        <span class="sort-handle"></span>
        <?php if ($field->get('associative')): ?>
        <input <?= $this->attr([
            'type'        => 'text',
            'class'       => 'array-input-key',
            'value'       => $key,
            'placeholder' => $field->get('placeholder_key')
        ]) ?>>
        <?php endif; ?>
        <input <?= $this->attr([
            'type'        => 'text',
            'class'       => 'array-input-value',
            'name'        => $field->formName() . ($field->get('associative') ? '[' . $key . ']' : '[]'),
            'value'       => $value,
            'placeholder' => $field->get('placeholder_value')
        ]) ?>>
        <button type="button" class="button-link array-input-remove" title="<?= $this->translate('fields.array.remove') ?>" aria-label="<?= $this->translate('fields.array.remove') ?>"></button>
        <button type="button" class="button-link array-input-add" title="<?= $this->translate('fields.array.add') ?>" aria-label="<?= $this->translate('fields.array.add') ?>"></button>
    </div>
<?php endforeach; ?>
</div>
