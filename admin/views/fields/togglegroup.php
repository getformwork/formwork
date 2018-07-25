<fieldset class="toggle-group">
<?php foreach ((array) $field->get('options') as $value => $label): ?>
    <label>
        <input type="radio" name="<?= $field->name() ?>" value="<?= $value ?>"<?php if ($value == $field->value()): ?> checked<?php endif; ?>>
        <span><?= $label ?></span>
    </label>
<?php endforeach; ?>
</fieldset>
