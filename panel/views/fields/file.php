<?= $this->layout('fields.field') ?>
<input <?= $this->attr([
    'type'             => 'file',
    'class'            => 'input-file',
    'id'               => $field->name(),
    'name'             => $field->formName() . '[]',
    'accept'           => $field->get('accept', implode(', ', $formwork->config()->get('files.allowedExtensions'))),
    'data-auto-upload' => $field->get('autoUpload') ? 'true' : 'false',
    'multiple'         => $field->get('multiple'),
    'required'         => $field->isRequired(),
    'disabled'         => $field->isDisabled(),
    'hidden'           => $field->isHidden()
]) ?>>
<label for="<?= $field->name() ?>" class="input-file-label">
    <span><?= $this->translate('fields.file.uploadLabel') ?></span>
</label>
