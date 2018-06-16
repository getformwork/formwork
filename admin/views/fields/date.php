<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<div class="input-wrap">
	<input class="date-input" type="text" id="<?= $field->name() ?>" name="<?= $field->name() ?>" value="<?= $field->value() ?>" placeholder="<?= $field->placeholder() ?>">
	<span class="input-reset" data-reset="<?= $field->name() ?>"><i class="i-times-circle"></i></span>
</div>
