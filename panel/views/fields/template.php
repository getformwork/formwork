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
        <?php $scheme = $app->schemes()->get('pages.' . $template->name()) ?>
        <option value="<?= $template->name() ?>" <?php if ($template->name() === (string) $field->value()) : ?> selected<?php endif ?>><?= $scheme->title() ?></option>
    <?php endforeach ?>
</select>