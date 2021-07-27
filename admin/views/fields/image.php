<?= $this->insert('fields.label', ['field' => $field]) ?>
<div class="input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => 'input-image',
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => basename($field->value()),
        'placeholder' => $field->placeholder(),
        'readonly'    => true
    ]) ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><?= $this->icon('times-circle') ?></span>
</div>
