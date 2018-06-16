<?php use Formwork\Utils\FileSystem; ?>
<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<input class="image-input" type="text" id="<?= $field->name() ?>" name="<?= $field->name() ?>" value="<?= FileSystem::basename($field->value()) ?>" placeholder="<?= $field->placeholder() ?>" readonly>
