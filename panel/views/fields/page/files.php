<ul class="files-list">
<?php
foreach ($page->files() as $file):
    ?>
    <li>
        <div class="files-item">
            <?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?> <div class="files-item-cell file-name" data-overflow-tooltip="true"><?= $file->name() ?> <span class="file-size">(<?= $file->size() ?>)</span></div>
            <div class="files-item-cell file-actions">
                <a class="button button-link" role="button" href="<?= $page->uri($file->name(), includeLanguage: false) ?>" target="formwork-preview-file-<?= $file->hash() ?>" title="<?= $this->translate('panel.pages.previewFile') ?>" aria-label="title="<?= $this->translate('panel.pages.previewFile') ?>""><?= $this->icon('eye') ?></a>
<?php
        if ($panel->user()->permissions()->has('pages.deleteFiles')):
            ?>
                <button type="button" class="button-link" data-modal="deleteFileModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/delete/') ?>" title="<?= $this->translate('panel.pages.deleteFile') ?>" aria-label="<?= $this->translate('panel.pages.deleteFile') ?>">
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
