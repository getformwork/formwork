<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.tools.tools') ?></div>
</div>

<?= $tabs ?>
<div data-view="backups">
    <section class="section">
        <button type="button" class="button button-secondary mr-6" data-command="make-backup"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.backup.backup') ?></button>
        <p class="mt-4 mb-0"><?= $this->translate('panel.tools.latestBackup') ?> <span class="text-bold backup-last-time"><?= $backups->isEmpty() ? $this->translate('date.never') : $this->timedistance($backups->first()['timestamp']) ?></span></p>
    </section>
    <section id="backups-section" class="section" <?php if ($backups->isEmpty()) : ?>hidden<?php endif ?>>
        <div class="section-header">
            <div class="caption"><?= $this->translate('panel.tools.latestBackups') ?></div>
        </div>
        <table id="backups-table" class="table table-bordered table-hoverable text-size-sm">
            <thead>
                <tr>
                    <th class="table-header truncate" style="width: 100%"><?= $this->translate('panel.tools.backup.file') ?></th>
                    <th class="table-header truncate text-align-center show-from-md" style="width: 25%"><?= $this->translate('panel.tools.backup.date') ?></th>
                    <th class="table-header truncate text-align-center show-from-sm" style="width: 15%"><?= $this->translate('panel.tools.backup.size') ?></th>
                    <th class="table-header text-align-center" style="width: 15%"><?= $this->translate('panel.tools.backup.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup) : ?>
                    <tr>
                        <td class="table-cell truncate"><?= $this->icon('file-archive') ?> <a href="<?= $panel->uri('/backup/download/' . $backup['encodedName']) . '/' ?>"><?= $backup['name'] ?></a></td>
                        <td class="table-cell truncate text-align-center show-from-md"><?= $this->datetime($backup['timestamp']) ?></td>
                        <td class="table-cell truncate text-align-center show-from-sm"><?= $backup['size'] ?></td>
                        <td class="table-cell text-align-center">
                            <button type="button" class="button button-link" data-modal="deleteFileModal" data-modal-action="<?= $panel->uri('/backup/delete/' . $backup['encodedName']) . '/' ?>" title="<?= $this->translate('panel.tools.backup.delete') ?>" aria-label="<?= $this->translate('panel.tools.backup.delete') ?>"><?= $this->icon('trash') ?></button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
</div>

<template id="backups-row">
    <tr>
        <td class="table-cell truncate"><?= $this->icon('file-archive') ?> <a class="backup-uri" href=""></a></td>
        <td class="table-cell truncate text-align-center backup-date show-from-md"></td>
        <td class="table-cell truncate text-align-center backup-size show-from-sm"></td>
        <td class="table-cell text-align-center">
            <button type="button" class="button button-link backup-delete" data-modal="deleteFileModal" data-modal-action="" title="<?= $this->translate('panel.tools.backup.delete') ?>" aria-label="<?= $this->translate('panel.tools.backup.delete') ?>"><?= $this->icon('trash') ?></button>
        </td>
    </tr>
</template>