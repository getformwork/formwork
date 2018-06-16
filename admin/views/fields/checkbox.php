<div>
    <label class="checkbox-label">
        <input type="checkbox" class="custom-checkbox" id="<?= $field->name() ?>" name="<?= $field->name() ?>"<?php if ($field->value() == true): ?> checked<?php endif; ?>>
        <span class="custom-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
