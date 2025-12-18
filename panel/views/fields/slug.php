<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon')]) ?>
    <div class="form-input-wrap">
        <input <?= $this->attr([
                    'class'            => ['form-input', 'form-input-slug', $field->get('class')],
                    'type'             => 'text',
                    'id'               => $field->name(),
                    'name'             => $field->formName(),
                    'value'            => $field->value(),
                    'placeholder'      => $field->placeholder(),
                    'minlength'        => $field->get('min'),
                    'maxlength'        => $field->get('max'),
                    'pattern'          => $field->get('pattern'),
                    'required'         => $field->isRequired(),
                    'disabled'         => $field->isDisabled(),
                    'hidden'           => $field->isHidden(),
                    'readonly'         => $field->isReadonly(),
                    'data-source'      => $field->source()?->name(),
                    'data-auto-update' => $field->autoUpdate() ? 'true' : 'false',
                ]) ?>>
        <?php if (!$field->autoUpdate()): ?>
            <span class="form-input-action" data-generate-slug="<?= $field->name() ?>" title="<?= $this->translate('panel.pages.changeSlug.generate') ?>"><?= $this->icon('sparks') ?></span>
        <?php endif ?>
    </div>
</div>