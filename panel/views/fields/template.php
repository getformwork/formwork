<?php $this->layout('fields.field') ?>
<select <?= $this->attr([
            'class'    => 'form-select',
            'id'       => $field->name(),
            'name'     => $field->formName(),
            'required' => $field->isRequired(),
            'disabled' => $field->isDisabled(),
            'hidden'   => $field->isHidden(),
        ]) ?>>
    <?php foreach ($site->templates() as $template) : ?>
        <option value="<?= $template->name() ?>" <?php if ($template->name() === (string) $field->value()) : ?> selected<?php endif ?>><?= $this->escape($template->title()) ?></option>
    <?php endforeach ?>
</select>