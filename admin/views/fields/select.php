<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<select id="<?= $field->name() ?>" name="<?= $field->name() ?>">
<?php foreach ((array) $field->get('options') as $value => $label): ?>
	<option value="<?= $value ?>"<?php if ($value == $field->value()): ?> selected<?php endif; ?>><?= $label ?></option>
<?php endforeach; ?>
</select>
