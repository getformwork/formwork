<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'template')]) ?>
    <select <?= $this->attr([
                'class'            => $this->classes(['form-select', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
        <?php foreach ($site->templates() as $template) : ?>
            <option <?= $this->attr(['value' => $template->name(), 'selected' => $template->name() === (string) $field->value(), 'data-icon' => $template->scheme()->options()->get('icon', 'page')]) ?>><?= $this->escape($template->title()) ?></option>
        <?php endforeach ?>
    </select>
</div>
