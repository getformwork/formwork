<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<div class="tag-input">
	<input class="tag-inner-input" id="<?= $field->name() ?>" type="text" size="1">
	<input class="tag-hidden-input" name="<?= $field->name() ?>" hidden readonly value="<?= implode(', ', (array) $field->value()) ?>">
</div>
