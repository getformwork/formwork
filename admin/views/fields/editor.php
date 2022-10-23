<?= $this->insert('fields.label', ['field' => $field]) ?>
<div class="editor-wrap">
    <div class="editor-toolbar" data-for="<?= $field->name() ?>">
        <button type="button" class="toolbar-button" data-command="bold" title="<?= $this->translate('admin.pages.editor.bold') ?>"><?= $this->icon('bold') ?></button>
        <button type="button" class="toolbar-button" data-command="italic" title="<?= $this->translate('admin.pages.editor.italic') ?>"><?= $this->icon('italic') ?></button>
        <button type="button" class="toolbar-button" data-command="ul" title="<?= $this->translate('admin.pages.editor.bullet-list') ?>"><?= $this->icon('list-unordered') ?></button>
        <button type="button" class="toolbar-button" data-command="ol" title="<?= $this->translate('admin.pages.editor.numbered-list') ?>"><?= $this->icon('list-ordered') ?></button>
        <span class="spacer"></span>
        <button type="button" class="toolbar-button" data-command="quote" title="<?= $this->translate('admin.pages.editor.quote') ?>"><?= $this->icon('blockquote') ?></button>
        <button type="button" class="toolbar-button" data-command="link" title="<?= $this->translate('admin.pages.editor.link') ?>"><?= $this->icon('link') ?></button>
        <button type="button" class="toolbar-button" data-command="image" title="<?= $this->translate('admin.pages.editor.image') ?>"><?= $this->icon('image') ?></button>
        <button type="button" class="toolbar-button" data-command="summary" title="<?= $this->translate('admin.pages.editor.summary') ?>"><?= $this->icon('readmore') ?></button>
        <span class="spacer"></span>
        <button type="button" class="toolbar-button" data-command="undo" title="<?= $this->translate('admin.pages.editor.undo') ?>" disabled><?= $this->icon('rotate-left') ?></button>
        <button type="button" class="toolbar-button" data-command="redo" title="<?= $this->translate('admin.pages.editor.redo') ?>" disabled><?= $this->icon('rotate-right') ?></button>
    </div>
    <textarea <?= $this->attr([
        'class'        => 'editor-textarea',
        'id'           => $field->name(),
        'name'         => $field->formName(),
        'placeholder'  => $field->placeholder(),
        'required'     => $field->isRequired(),
        'disabled'     => $field->isDisabled(),
        'autocomplete' => 'off'
    ]) ?>><?= $this->escape($field->value() ?? '') ?></textarea>
</div>
