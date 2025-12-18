<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon', 'template')]) ?>
    <select <?= $this->attr([
                'class'    => 'form-select',
                'id'       => $field->name(),
                'name'     => $field->formName(),
                'required' => $field->isRequired(),
                'disabled' => $field->isDisabled(),
                'hidden'   => $field->isHidden(),
            ]) ?>>
        <?php foreach ($site->templates() as $template) : ?>
            <option <?= $this->attr(['value' => $template->name(), 'selected' => $template->name() === (string) $field->value(), 'data-icon' => $template->scheme()->options()->get('icon', 'page')]) ?>><?= $this->escape($template->title()) ?></option>
        <?php endforeach ?>
    </select>
</div>