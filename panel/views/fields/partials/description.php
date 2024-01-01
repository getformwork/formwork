<?php if ($field->has('description')): ?>
    <div class="text-color-gray-light text-size-s mb-6"><?= $this->markdown($field->get('description')) ?></div>
<?php endif ?>
