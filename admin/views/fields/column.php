<div class="col-m-<?= $field->get('width') ?>">
<?php if ($field->has('label')): ?>
	<?= $field->label() ?>
<?php else: ?>
	<?php $this->fields($field->get('fields')) ?>
<?php endif; ?>
</div>
