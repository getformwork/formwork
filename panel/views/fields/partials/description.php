<?php if ($field->has('description')): ?>
<div class="text-light text-s mb-6"><?= $this->markdown($field->get('description')); ?></div>
<?php endif ?>
