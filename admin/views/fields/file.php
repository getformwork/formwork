<?= $this->insert('fields.label', ['field' => $field]) ?>
<input <?= $this->attr([
    'type'   => 'file',
    'class'  => 'input-file',
    'id'     => $field->name(),
    'name'   => $field->formName(),
    'accept' => $field->get('accept'),
]) ?>>
<label for="<?= $field->name() ?>" class="input-file-label">
    <span><?= $this->translate('fields.file.upload-label') ?></span>
</label>
