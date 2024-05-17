<?php $this->layout('fields.field') ?>
<div class="image-picker-empty-state">
    <span class="image-picker-empty-state-icon"><?= $this->icon('image') ?></span>
    <h4 class="h5"><?= $this->translate('panel.modal.images.noImages') ?></h4>
</div>
<select class="form-input image-picker">
    <?php foreach ($page->images() as $image) : ?>
        <option value="<?= $page->uri($image, includeLanguage: false) ?>" data-thumbnail="<?= $image->square(300, 'contain')->uri() ?>"><?= $image ?></option>
    <?php endforeach ?>
</select>