<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon')]) ?>
    <div class="form-input-wrap">
        <input <?= $this->attr([
                    'class'            => $this->classes(['form-input', 'form-input-slug', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                    'type'             => 'text',
                    'id'               => $field->name(),
                    'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
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
            <button type="button" class="form-input-action" data-generate-slug="<?= $field->name() ?>" title="<?= $this->translate('panel.pages.changeSlug.generate') ?>" aria-label="<?= $this->translate('panel.pages.changeSlug.generate') ?>"><?= $this->icon('sparks') ?></button>
        <?php endif ?>
    </div>
</div>
