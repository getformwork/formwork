<?php $this->layout('fields.field') ?>
<div class="image-picker-empty-state">
    <span class="image-picker-empty-state-icon"><?= $this->icon('images') ?></span>
    <h4 class="h5"><?= $this->translate('panel.modal.images.noImages') ?></h4>
</div>
<?php if (($model = $field->parent()->model())?->has('files')): ?>
    <select class="form-input image-picker" id="<?= $field->name() ?>">
        <?php foreach ($model->files()->filterBy('type', 'image') as $image) : ?>
            <option <?= $this->attr([
                        'value'          => $page->uri($image, includeLanguage: false),
                        'data-thumbnail' => $image->square(300, 'contain')->uri(),
                    ]) ?>><?= $image ?></option>
        <?php endforeach ?>
    </select>
<?php endif ?>