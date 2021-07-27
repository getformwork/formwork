<?php $this->layout('admin') ?>
<form method="post" data-form="page-editor-form" enctype="multipart/form-data">
    <div class="row">
        <div class="col-l-3-4">
            <div class="component">
                <h3 class="caption"><?= $this->translate('admin.pages.content') ?></h3>
                <input class="input-large" id="title" type="text" name="title" value="<?= $this->escapeAttr($page->title()) ?>" required autocomplete="off">
                <input type="hidden" id="slug" name="slug" value="<?= $page->slug() ?>">
<?php
                if ($currentLanguage):
?>
                <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
<?php
                endif;
?>
                <div class="page-info">
                    <?php if (!$page->isIndexPage() && !$page->isErrorPage()): ?>
                        <div class="page-route page-route-changeable">
                            <button type="button" class="page-slug-change" data-command="change-slug" title="<?= $this->translate('admin.pages.change-slug') ?>"><?= $page->route() ?> <?= $this->icon('pencil') ?></button>
                        </div>
                    <?php else: ?>
                        <div class="page-route"><span><?= $page->route() ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="editor-wrap">
                    <div class="editor-toolbar" data-for="content">
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
                    <textarea class="editor-textarea" id="content" name="content" autocomplete="off"><?= $this->escape($page->rawContent()) ?></textarea>
                </div>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                <button type="submit" class="button-accent button-right" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('admin.pages.save') ?></button>
<?php
                if ($availableLanguages):
?>
                <div class="dropdown button-right">
                    <button type="button" class="dropdown-button button-accent" data-dropdown="languages-dropdown"><?= $this->icon('translate') ?> <?= $this->translate('admin.pages.languages') ?><?php if ($currentLanguage): ?> <span class="page-language"><?= $currentLanguage ?></span><?php endif; ?></button>
                    <div class="dropdown-menu" id="languages-dropdown">
<?php
                    foreach ($availableLanguages as $languageCode => $languageLabel):
?>
                        <a href="<?= $admin->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $languageCode . '/') ?>" class="dropdown-item"><?= $page->hasLanguage($languageCode) ? $this->translate('admin.pages.languages.edit-language', $languageLabel) : $this->translate('admin.pages.languages.add-language', $languageLabel); ?></a>
<?php
                    endforeach;
?>

                    </div>
                </div>
<?php
                endif;
?>
<?php
            if ($admin->user()->permissions()->has('pages.delete')):
?>
                <button type="button" class="button-link button-right" data-modal="deletePageModal" data-modal-action="<?= $admin->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" title="<?= $this->translate('admin.pages.delete-page') ?>" aria-label="<?= $this->translate('admin.pages.delete-page') ?>" <?php if (!$page->isDeletable()): ?> disabled<?php endif; ?>><?= $this->icon('trash') ?></button>
<?php
            endif;
?>
                <a class="button button-link button-right<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif; ?>" role="button" <?php if ($page->published() && $page->routable()): ?>href="<?= $admin->pageUri($page, $currentLanguage ?: true) ?>"<?php endif; ?> target="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('admin.pages.preview') ?>" aria-label="<?= $this->translate('admin.pages.preview') ?>"><?= $this->icon('eye') ?></a>
            </div>
<?php
        if ($admin->user()->permissions()->has('pages.upload_files') || !$page->files()->isEmpty()):
?>
            <div class="component">
                <h3 class="caption"><?= $this->translate('admin.pages.files') ?></h3>
                <ul class="files-list">
<?php
                foreach ($page->files() as $file):
?>
                    <li>
                        <div class="files-item">
                            <?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?> <div class="files-item-cell file-name" data-overflow-tooltip="true"><?= $file->name() ?> <span class="file-size">(<?= $file->size() ?>)</span></div>
                            <div class="files-item-cell file-actions">
                                <a class="button button-link" role="button" href="<?= $admin->pageUri($page) . $file->name() ?>" target="formwork-preview-file-<?= $file->hash() ?>" title="<?= $this->translate('admin.pages.preview-file') ?>" aria-label="title="<?= $this->translate('admin.pages.preview-file') ?>""><?= $this->icon('eye') ?></a>
<?php
                    if ($admin->user()->permissions()->has('pages.delete_files')):
?>
                                <button type="button" class="button-link" data-modal="deleteFileModal" data-modal-action="<?= $admin->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/delete/') ?>" title="<?= $this->translate('admin.pages.delete-file') ?>" aria-label="<?= $this->translate('admin.pages.delete-file') ?>">
                                    <?= $this->icon('trash') ?>
                                </button>
<?php
                    endif;
?>
                            </div>
                        </div>
                    </li>
<?php
                endforeach;
?>
                </ul>
<?php
                if ($admin->user()->permissions()->has('pages.upload_files')):
?>
                <input class="input-file" id="file-uploader" type="file" name="uploaded-file" data-auto-upload="true" accept="<?= implode(', ', $formwork->config()->get('files.allowed_extensions')) ?>">
                <label for="file-uploader" class="input-file-label">
                    <span><?= $this->translate('fields.file.upload-label') ?></span>
                </label>
<?php
                endif;
?>
            </div>
<?php
        endif;
?>
        </div>
        <div class="col-l-1-4">
            <div class="component">
                <h3 class="caption"><?= $this->translate('admin.pages.options') ?></h3>
                <?php $this->insert('fields', ['fields' => $fields]) ?>
            </div>
            <div class="component">
                <h3 class="caption"><?= $this->translate('admin.pages.attributes') ?></h3>
                <label for="page-parent"><?= $this->translate('admin.pages.parent') ?>:</label>
                <select id="page-parent" name="parent">
                    <option value="." <?php if ($page->parent()->isSite()): ?> selected<?php endif; ?>><?= $this->translate('admin.pages.new-page.site') ?> (/)</option>
<?php
                    foreach ($parents as $parent):
                        $scheme = $formwork->schemes()->get('pages', $parent->template()->name());
                        if (!$scheme->get('pages', true)) continue;
                        if ($parent === $page) continue;
?>
                    <option value="<?= $parent->route() ?>"<?php if ($page->parent() === $parent): ?> selected<?php endif; ?>><?= str_repeat('— ', $parent->level() - 1) . $parent->title() ?></option>
<?php
                    endforeach;
?>
                </select>
                <label for="page-template"><?= $this->translate('admin.pages.template') ?>:</label>
                <select id="page-template" name="template">
<?php
                foreach ($templates as $template):
                    $scheme = $formwork->schemes()->get('pages', $template);
?>
                    <option value="<?= $template ?>"<?php if ($page->template()->name() === $template): ?> selected<?php endif; ?>><?= $scheme->title() ?></option>
<?php
                endforeach;
?>
                </select>
            </div>
        </div>
    </div>
</form>
