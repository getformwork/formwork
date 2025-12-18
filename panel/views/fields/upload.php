<?php if ($field->get('listFiles', false) && ($model = $field->parent()?->model())) : ?>
    <?php $this->insert('partials.files.file.list', ['name' => $field->name(), 'files' => $field->collection()->map(fn($file) => [$file, $model]), 'columns' => ['date', 'size']]); ?>
<?php endif ?>
<?php if ($field->has('label')) : ?>
    <label class="<?= $this->classes(['form-label', 'form-label-required' => $field->isRequired()]) ?>" for="<?= $field->name() ?>"><?= $this->escape($this->append($field->label(), ':')) ?></label>
    <?php if ($field->has('suggestion')) : ?><span class="form-label-suggestion">(<?= $this->escape($field->get('suggestion')) ?>)</span><?php endif ?>
<?php endif ?>
<label class="form-upload-drop-target" tabindex="0">
    <input <?= $this->attr([
                'type'             => 'file',
                'class'            => ['form-input', 'form-input-upload'],
                'id'               => $field->name(),
                'name'             => $field->formName() . ($field->get('multiple') ? '[]' : ''),
                'accept'           => $field->get('accept', implode(', ', $app->config()->get('system.files.allowedExtensions'))),
                'multiple'         => $field->get('multiple'),
                'required'         => false,
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
                'data-auto-upload' => $field->autoUpload() ? 'true' : 'false',
            ]) ?>>
    <span><?= $this->icon('cloud-upload') ?> <?= $this->translate('fields.file.uploadLabel') ?></span>
</label>
<?php $this->insert('fields.partials.description') ?>
