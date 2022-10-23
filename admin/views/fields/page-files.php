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
