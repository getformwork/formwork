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
        <span class="button button-link array-input-remove" title="<?= $this->label('fields.array.remove') ?>"></span>
        <span class="button button-link array-input-add" title="<?= $this->label('fields.array.add') ?>"></span>
    </div>
<?php endforeach; ?>
</div>
