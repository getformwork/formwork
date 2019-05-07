<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<div class="input-wrap">
    <input class="date-input" type="text" id="<?= $field->name() ?>" name="<?= $field->formName() ?>" value="<?= $field->value() ?>" placeholder="<?= $field->placeholder() ?>"<?php if ($field->get('required')): ?> required<?php endif; ?><?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
