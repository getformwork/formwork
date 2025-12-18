<?php $this->layout('fields.field') ?>
<fieldset <?= $this->attr([
                'class'  => $this->classes(['form-input-array', 'form-input-array-associative' => $field->isAssociative()]),
                'id'     => $field->name(),
                'name'   => $field->formName(),
                'hidden' => $field->isHidden(),
            ]) ?>>
    <?php foreach ($field->items(default: $field->isAssociative() ? ['' => ''] : ['']) as $key => $items) : ?>
        <div class="form-input-array-row">
            <span class="sortable-handle" title="<?= $this->translate('panel.dragToReorder') ?>"><?= $this->icon('grabber') ?></span>
            <div class="form-input-array-item form-input-array-item-type-<?= $items->type() ?>">
                <?php if ($field->isAssociative()) : ?>
                    <input <?= $this->attr([
                                'type'        => 'text',
                                'class'       => ['form-input', 'form-input-array-key'],
                                'value'       => $items->get('itemKey'),
                                'placeholder' => $field->get('placeholderKey'),
                                'required'    => !empty($items->value()),
                            ]) ?>>
                <?php endif ?>
                <div class="form-input-array-value">
                    <?php $this->insert('fields.' . $items->type(), ['field' => $items]) ?>
                </div>
            </div>
            <button type="button" class="button button-link form-input-array-remove" title="<?= $this->translate('fields.array.remove') ?>" aria-label="<?= $this->translate('fields.array.remove') ?>"><?= $this->icon('minus-circle') ?></button>
            <button type="button" class="button button-link form-input-array-add" title="<?= $this->translate('fields.array.add') ?>" aria-label="<?= $this->translate('fields.array.add') ?>"><?= $this->icon('plus-circle') ?></button>
        </div>
    <?php endforeach ?>
</fieldset>
