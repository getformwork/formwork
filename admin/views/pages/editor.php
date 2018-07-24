<div class="container-no-margin">
    <div class="row">
        <form action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/edit/') ?>" method="post" data-form="page-editor-form">
            <div class="col-l-3-4">
                <div class="component">
                    <h3 class="caption"><?= $this->label('pages.content') ?></h3>
                    <input class="title-input" id="title" type="text" name="title" tabindex="1" value="<?= htmlspecialchars($page->title()) ?>" required autocomplete="off">
                    <div class="page-info">
                        <div class="page-uri">
                            <a <?php if ($page->published() && $page->routable()): ?>href="<?= $this->pageUri($page) ?>"<?php endif; ?> target="_blank"><?= $page->slug() ?></a>
                        </div>
                    </div>
                    <div class="editor-toolbar" data-for="content">
                        <button class="toolbar-button" tabindex="-1" data-command="bold" title="<?= $this->label('pages.editor.bold') ?>" type="button"><span class="i-bold"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="italic" title="<?= $this->label('pages.editor.italic') ?>" type="button"><span class="i-italic"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="ul" title="<?= $this->label('pages.editor.bullet-list') ?>" type="button"><span class="i-list-ul"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="ol" title="<?= $this->label('pages.editor.numbered-list') ?>" type="button"><span class="i-list-ol"></span></button>
                        <span class="spacer"></span>
                        <button class="toolbar-button" tabindex="-1" data-command="quote" title="<?= $this->label('pages.editor.quote') ?>" type="button"><span class="i-quote-left"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="link" title="<?= $this->label('pages.editor.link') ?>" type="button"><span class="i-link"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="image" title="<?= $this->label('pages.editor.image') ?>" type="button"><span class="i-image"></span></button>
                        <button class="toolbar-button" tabindex="-1" data-command="summary" title="<?= $this->label('pages.editor.summary') ?>" type="button"><span class="i-read-more-alt"></span></button>
                    </div>
                    <textarea tabindex="2" class="editor-textarea" id="content" name="content" autocomplete="off"><?= htmlspecialchars($page->rawContent()) ?></textarea>
                    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                    <button class="button-accent button-save button-right" type="submit" tabindex="4" data-command="save"><i class="i-check"></i> <?= $this->label('pages.save') ?></button>
                    <button class="button-link button-right" tabindex="-1" type="button" data-modal="deletePageModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/delete/') ?>" title="<?= $this->label('pages.delete-page') ?>" <?php if (!$page->isDeletable()): ?> disabled<?php endif; ?>><i class="i-trash"></i></button>
                    <a class="button button-link button-right<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif; ?>" <?php if ($page->published() && $page->routable()): ?>href="<?= $this->pageUri($page) ?>"<?php endif; ?> target="_blank" title="<?= $this->label('pages.preview') ?>"><i class="i-eye"></i></a>
                </div>
            </div>
            <div class="col-l-1-4">
                <div class="component">
                    <h3 class="caption"><?= $this->label('pages.options') ?></h3>
<?php
                    foreach ($this->fields as $field):
?>
                    <?= $this->field($field->name()) ?>
<?php
                    endforeach;
?>
                </div>
            </div>
        </form>
        <div class="col-l-3-4">
            <div class="component">
                <h3 class="caption"><?= $this->label('pages.files') ?></h3>
                <ul class="files-list">
<?php
                foreach ($page->files() as $file):
?>
                    <li>
                        <div class="files-item">
                            <div class="files-item-cell file-name <?= is_null($file->type()) ? '' : 'file-type-' . $file->type() ?>"><?= $file->name() ?></div>
                            <div class="files-item-cell file-actions">
                                <a class="button button-link" href="<?= $this->pageUri($page) . $file->name() ?>" target="_blank" title="<?= $this->label('pages.preview-file') ?>"><i class="i-eye"></i></a>
                                <button class="button-link" type="button" data-modal="deleteFileModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/file/' . $file->name() . '/delete/') ?>" title="<?= $this->label('pages.delete-file') ?>">
                                    <i class="i-trash"></i>
                                </button>
                            </div>
                        </div>
                    </li>
<?php
                endforeach;
?>
                </ul>
                <form action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/file/upload/') ?>" method="post" enctype="multipart/form-data">
                    <input class="file-input" id="file-uploader" type="file" name="uploaded-file" data-auto-upload="true" accept="<?= implode(', ', $this->formwork()->option('files.allowed_extensions')) ?>">
                    <label for="file-uploader" class="file-input-label">
                        <span><?= $this->label('pages.files.upload-label') ?></span>
                    </label>
                    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$('.date-input').datePicker({
    dayLabels: <?= json_encode($this->label('date.weekdays.short')) ?>,
    monthLabels: <?= json_encode($this->label('date.months.long')) ?>,
    weekStarts: <?= json_encode($this->formwork()->option('date.week_starts')) ?>,
    todayLabel: <?= json_encode($this->label('date.today')) ?>,
    format: <?= json_encode(strtr($this->formwork()->option('date.format'), array('Y' => 'YYYY', 'm' => 'MM', 'd' => 'DD', 'H' => 'hh', 'i' => 'mm', 's' => 'ss'))) ?>
});
</script>
