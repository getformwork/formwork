<?php if ($field->has('description')) : ?>
    <div class="form-input-description"><?= $this->markdown($field->get('description')) ?></div>
<?php endif ?>