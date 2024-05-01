<?php if ($field->has('label')) : ?>
    <label class="<?= $this->classes(['form-label', 'form-label-required' => $field->isRequired()]) ?>" for="<?= $field->name() ?>"><?= $this->append($field->label(), ':') ?></label>
<?php endif ?>