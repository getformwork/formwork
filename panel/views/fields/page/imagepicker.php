<?php $this->layout('@panel.fields.field') ?>
<?php if ($model = $field->parent()->model()): ?>
    <div class="image-picker-empty-state">
        <span class="image-picker-empty-state-icon"><?= $this->icon('images') ?></span>
        <h4 class="h5"><?= $this->translate('panel.modal.images.noImages') ?></h4>
    </div>

    <input type="hidden" class="form-input image-picker" id="<?= $field->name() ?>" name="<?= $field->formName() ?>" data-src="<?= $this->uri($app->router()->generate('panel.files.list', ['model' => $model->getModelIdentifier(), 'id' => $model->route()])) ?>">
<?php endif ?>
