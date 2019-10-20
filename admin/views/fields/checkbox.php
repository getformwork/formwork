<div>
    <label class="checkbox-label">
        <input type="checkbox" class="custom-checkbox" id="<?= $field->name() ?>" name="<?= $field->formName() ?>"<?php if ($field->value() == true): ?> checked<?php endif; ?><?php if ($field->get('required')): ?> required<?php endif; ?><?php if ($field->get('disabled')): ?> disabled<?php endif; ?>>
        <span class="custom-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
