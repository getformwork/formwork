<?php if ($field->has('description')): ?>
<div style="font-size: 0.875rem; color: #7d7d7d; margin-bottom: 0.75rem;"><?= $this->markdown($field->get('description')); ?></div>
<?php endif; ?>
