<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<div class="tag-input<?php if ($field->get('disabled')): ?> disabled<?php endif; ?>">
    <input class="tag-inner-input" id="<?= $field->name() ?>" type="text" size="1"<?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
    <input class="tag-hidden-input" name="<?= $field->formName() ?>" hidden readonly value="<?= implode(', ', (array) $field->value()) ?>"<?php if ($field->get('required')): ?> required<?php endif; ?><?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
</div>
