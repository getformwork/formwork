<?php if ($field->has('description')) : ?>
    <div class="text-color-gray-light text-size-sm mb-6"><?= $this->markdown($field->get('description')) ?></div>
<?php endif ?>