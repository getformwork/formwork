<div class="container-no-margin">
    <form method="post" data-form="page-editor-form" enctype="multipart/form-data">
        <div class="row">
            <div class="col-l-3-4">
                <div class="component">
                    <h3 class="caption"><?= $this->label('pages.content') ?></h3>
                    <input class="title-input" id="title" type="text" name="title" value="<?= $this->escapeAttr($page->title()) ?>" required autocomplete="off">
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
                                <button type="button" class="page-slug-change" data-command="change-slug" title="<?= $this->label('pages.change-slug') ?>"><?= $page->route() ?></button>
                            </div>
                        <?php else: ?>
                            <div class="page-route"><span><?= $page->route() ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="editor-toolbar" data-for="content">
                        <button type="button" class="toolbar-button" data-command="bold" title="<?= $this->label('pages.editor.bold') ?>"><span class="i-bold"></span></button>
                        <button type="button" class="toolbar-button" data-command="italic" title="<?= $this->label('pages.editor.italic') ?>"><span class="i-italic"></span></button>
                        <button type="button" class="toolbar-button" data-command="ul" title="<?= $this->label('pages.editor.bullet-list') ?>"><span class="i-list-ul"></span></button>
                        <button type="button" class="toolbar-button" data-command="ol" title="<?= $this->label('pages.editor.numbered-list') ?>"><span class="i-list-ol"></span></button>
                        <span class="spacer"></span>
                        <button type="button" class="toolbar-button" data-command="quote" title="<?= $this->label('pages.editor.quote') ?>"><span class="i-quote"></span></button>
                        <button type="button" class="toolbar-button" data-command="link" title="<?= $this->label('pages.editor.link') ?>"><span class="i-link"></span></button>
                        <button type="button" class="toolbar-button" data-command="image" title="<?= $this->label('pages.editor.image') ?>"><span class="i-image"></span></button>
                        <button type="button" class="toolbar-button" data-command="summary" title="<?= $this->label('pages.editor.summary') ?>"><span class="i-read-more"></span></button>
                        <span class="spacer"></span>
                        <button type="button" class="toolbar-button" data-command="undo" title="<?= $this->label('pages.editor.undo') ?>" disabled><span class="i-undo"></span></button>
                        <button type="button" class="toolbar-button" data-command="redo" title="<?= $this->label('pages.editor.redo') ?>" disabled><span class="i-redo"></span></button>
                    </div>
                    <textarea class="editor-textarea" id="content" name="content" autocomplete="off"><?= $this->escape($page->rawContent()) ?></textarea>
                    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                    <button type="submit" class="button-accent button-right" data-command="save"><i class="i-check"></i> <?= $this->label('pages.save') ?></button>
<?php
                    if ($availableLanguages):
?>
                    <div class="dropdown button-right">
                        <button type="button" class="dropdown-button button-accent" data-dropdown="languages-dropdown"><i class="i-language"></i> <?= $this->label('pages.languages') ?><?php if ($currentLanguage): ?> <span class="page-language"><?= $currentLanguage ?></span><?php endif; ?></button>
                        <div class="dropdown-menu" id="languages-dropdown">
<?php
                        foreach ($availableLanguages as $languageCode => $languageLabel):
?>
                            <a href="<?= $this->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $languageCode . '/') ?>" class="dropdown-item"><?= $page->hasLanguage($languageCode) ? $this->label('pages.languages.edit-language', $languageLabel) : $this->label('pages.languages.add-language', $languageLabel); ?></a>
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
                    <button type="button" class="button-link button-right" data-modal="deletePageModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" title="<?= $this->label('pages.delete-page') ?>" aria-label="<?= $this->label('pages.delete-page') ?>" <?php if (!$page->isDeletable()): ?> disabled<?php endif; ?>><i class="i-trash"></i></button>
<?php
                endif;
?>
                    <a class="button button-link button-right<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif; ?>" role="button" <?php if ($page->published() && $page->routable()): ?>href="<?= $this->pageUri($page, $currentLanguage ?: true) ?>"<?php endif; ?> target="_blank" title="<?= $this->label('pages.preview') ?>" aria-label="<?= $this->label('pages.preview') ?>"><i class="i-eye"></i></a>
                </div>
<?php
            if ($admin->user()->permissions()->has('pages.upload_files') || !$page->files()->isEmpty()):
?>
                <div class="component">
                    <h3 class="caption"><?= $this->label('pages.files') ?></h3>
                    <ul class="files-list">
<?php
                    foreach ($page->files() as $file):
?>
                        <li>
                            <div class="files-item">
                                <div class="files-item-cell file-name <?= is_null($file->type()) ? '' : 'file-type-' . $file->type() ?>" data-overflow-tooltip="true"><?= $file->name() ?> <span class="file-size">(<?= $file->size() ?>)</span></div>
                                <div class="files-item-cell file-actions">
                                    <a class="button button-link" role="button" href="<?= $this->pageUri($page) . $file->name() ?>" target="_blank" title="<?= $this->label('pages.preview-file') ?>" aria-label="title="<?= $this->label('pages.preview-file') ?>""><i class="i-eye"></i></a>
<?php
                        if ($admin->user()->permissions()->has('pages.delete_files')):
?>
                                    <button type="button" class="button-link" data-modal="deleteFileModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/delete/') ?>" title="<?= $this->label('pages.delete-file') ?>" aria-label="<?= $this->label('pages.delete-file') ?>">
                                        <i class="i-trash"></i>
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
                    <input class="file-input" id="file-uploader" type="file" name="uploaded-file" data-auto-upload="true" accept="<?= implode(', ', $formwork->option('files.allowed_extensions')) ?>">
                    <label for="file-uploader" class="file-input-label">
                        <span><?= $this->label('pages.files.upload-label') ?></span>
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
                    <h3 class="caption"><?= $this->label('pages.options') ?></h3>
                    <?= $fields ?>
                </div>
                <div class="component">
                    <h3 class="caption"><?= $this->label('pages.attributes') ?></h3>
                    <label for="page-parent"><?= $this->label('pages.parent') ?>:</label>
                    <select id="page-parent" name="parent">
                        <option value="." <?php if ($page->parent()->isSite()): ?> selected<?php endif; ?>><?= $this->label('pages.new-page.site') ?> (/)</option>
<?php
                        foreach ($parents as $parent):
                            $scheme = $this->scheme($parent->template()->name());
                            if (!$scheme->get('pages', true)) continue;
                            if ($parent === $page) continue;
?>
                        <option value="<?= $parent->route() ?>"<?php if ($page->parent() === $parent): ?> selected<?php endif; ?>><?= str_repeat('— ', $parent->level() - 1) . $parent->title() ?></option>
<?php
                        endforeach;
?>
                    </select>
                    <label for="page-template"><?= $this->label('pages.template') ?>:</label>
                    <select id="page-template" name="template">
<?php
                    foreach ($templates as $template):
                        $scheme = $this->scheme($template);
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
</div>
