<?php $this->layout('fields.field') ?>
<div class="editor-wrap">
    <div class="editor-toolbar">
        <button type="button" class="button toolbar-button editor-toggle-markdown" data-command="toggle-markdown" title="<?= $this->translate('panel.editor.toggleMarkdown') ?>"><?= $this->icon('markdown') ?></button>
    </div>
    <textarea <?= $this->attr([
                    'class'         => ['form-textarea', 'editor-textarea'],
                    'id'            => $field->name(),
                    'name'          => $field->formName(),
                    'placeholder'   => $field->placeholder(),
                    'minlength'     => $field->get('min'),
                    'maxlength'     => $field->get('max'),
                    'autocomplete'  => $field->get('autocomplete', 'off'),
                    'spellcheck'    => $field->get('spellcheck', 'false'),
                    'rows'          => $field->get('rows', 15),
                    'required'      => $field->isRequired(),
                    'disabled'      => $field->isDisabled(),
                    'hidden'        => $field->isHidden(),
                    'data-base-uri' => $field?->parent()?->model()?->uri(),
                ]) ?>><?= $this->escape($field->value() ?? '') ?></textarea>
</div>