<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'page')]) ?>
    <select <?= $this->attr([
                'class'            => $this->classes(['form-select', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
        <?php if (!$field->isRequired()) : ?>
            <option <?= $this->attr([
                        'value' => '',
                        'selected' => $field->value() === '',
                    ]) ?>><?= $this->translate('page.none') ?></option>
        <?php endif ?>
        <?php if ($field->allowSite()) : ?>
            <option <?= $this->attr([
                        'value' => '.',
                        'selected' => $field->value() === '.',
                    ]) ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
        <?php endif ?>
        <?php foreach ($field->collection() as $page) : ?>
            <option <?= $this->attr([
                        'value' => $page->route(),
                        'selected' => $page->route() === $field->value(),
                        'data-allowed-templates' => $page->scheme()->options()->get('children.templates'),
                        'data-icon' => $page->icon(),
                    ]) ?>><?= str_repeat('— ', $page->level() - 1) . $this->escape($page->title()) ?></option>
        <?php endforeach ?>
    </select>
</div>
