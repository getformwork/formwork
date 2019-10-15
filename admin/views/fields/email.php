<?= $this->insert('fields.label') ?>
<input type="email" id="<?= $field->name() ?>" name="<?= $field->formName() ?>" value="<?= $field->value() ?>" placeholder="<?= $field->placeholder() ?>"<?php if ($field->get('required')): ?> required<?php endif; ?><?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
