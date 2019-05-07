<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<input id="<?= $field->name() ?>" name="<?= $field->formName() ?>" value="<?= $field->value() ?>" placeholder="<?= $field->placeholder() ?>"<?php if ($field->get('required')): ?> required<?php endif; ?><?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
