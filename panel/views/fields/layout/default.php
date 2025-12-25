<section class="section">
    <?php foreach ($fields as $field) : ?>
        <?php if ($field->isVisible()) : ?>
            <?php $this->insert("@panel.fields.{$field->type()}", ['field' => $field]) ?>
        <?php endif ?>
    <?php endforeach ?>
</section>