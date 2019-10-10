<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<div id="<?= $field->name() ?>" class="array-input<?php if ($field->get('associative')): ?> array-input-associative<?php endif; ?>" data-name="<?= $field->formName() ?>">
<?php foreach ($field->value() ?: array('' => '') as $key => $value): ?>
    <div class="array-input-row">
        <span class="sort-handle"></span>
    <?php if ($field->get('associative')): ?>
        <input class="array-input-key" type="text" value="<?= $key ?>" placeholder="<?= $field->get('placeholder_key') ?>">
    <?php endif; ?>
        <input class="array-input-value" name="<?= $field->formName() ?>[<?php if ($field->get('associative')): ?><?= $key ?><?php endif; ?>]" type="text" value="<?= $value ?>" placeholder="<?= $field->get('placeholder_value') ?>">
        <span class="button button-link array-input-remove" title="<?= $this->label('fields.array.remove') ?>"></span>
        <span class="button button-link array-input-add" title="<?= $this->label('fields.array.add') ?>"></span>
    </div>
<?php endforeach; ?>
</div>
