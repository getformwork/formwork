<?= $this->layout('fields.field') ?>
<div class="editor-wrap">
    <div class="editor-toolbar" data-for="<?= $field->name() ?>">
        <button type="button" class="toolbar-button" data-command="bold" title="<?= $this->translate('panel.pages.editor.bold') ?>"><?= $this->icon('bold') ?></button>
        <button type="button" class="toolbar-button" data-command="italic" title="<?= $this->translate('panel.pages.editor.italic') ?>"><?= $this->icon('italic') ?></button>
        <button type="button" class="toolbar-button" data-command="ul" title="<?= $this->translate('panel.pages.editor.bulletList') ?>"><?= $this->icon('list-unordered') ?></button>
        <button type="button" class="toolbar-button" data-command="ol" title="<?= $this->translate('panel.pages.editor.numberedList') ?>"><?= $this->icon('list-ordered') ?></button>
        <span class="spacer"></span>
        <button type="button" class="toolbar-button" data-command="quote" title="<?= $this->translate('panel.pages.editor.quote') ?>"><?= $this->icon('blockquote') ?></button>
        <button type="button" class="toolbar-button" data-command="link" title="<?= $this->translate('panel.pages.editor.link') ?>"><?= $this->icon('link') ?></button>
        <button type="button" class="toolbar-button" data-command="image" title="<?= $this->translate('panel.pages.editor.image') ?>"><?= $this->icon('image') ?></button>
        <span class="spacer"></span>
        <button type="button" class="toolbar-button" data-command="undo" title="<?= $this->translate('panel.pages.editor.undo') ?>" disabled><?= $this->icon('rotate-left') ?></button>
        <button type="button" class="toolbar-button" data-command="redo" title="<?= $this->translate('panel.pages.editor.redo') ?>" disabled><?= $this->icon('rotate-right') ?></button>
    </div>
    <textarea <?= $this->attr([
        'class'        => 'editor-textarea',
        'id'           => $field->name(),
        'name'         => $field->formName(),
        'placeholder'  => $field->placeholder(),
        'minlength'    => $field->get('min'),
        'maxlength'    => $field->get('max'),
        'autocomplete' => $field->get('autocomplete', 'off'),
        'rows'         => $field->get('rows', 15),
        'required'     => $field->isRequired(),
        'disabled'     => $field->isDisabled(),
        'hidden'       => $field->isHidden(),
    ]) ?>><?= $this->escape($field->value() ?? '') ?></textarea>
</div>
