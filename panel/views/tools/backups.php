<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.tools.tools') ?></div>
</div>

<?= $tabs ?>
<div data-view="backups">
    <section class="section">
        <button type="button" class="mr-6" data-command="make-backup"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.backup.backup') ?></button>
        <p class="mt-4 mb-0"><?= $this->translate('panel.tools.latestBackup') ?> <span class="text-bold backup-last-time"><?= $this->timedistance($backups[0]['timestamp']) ?></span></p>
    </section>

    <section class="section">
        <div class="section-header">
            <h3 class="caption"><?= $this->translate('panel.tools.latestBackups') ?></h3>
        </div>
        <table id="backups-table" class="table-bordered table-hoverable text-size-s">
            <thead>
                <tr>
                    <th class="truncate" style="width: 100%"><?= $this->translate('panel.tools.backup.file') ?></th>
                    <th class="truncate text-align-center show-from-m" style="width: 25%"><?= $this->translate('panel.tools.backup.date') ?></th>
                    <th class="truncate text-align-center show-from-s" style="width: 15%"><?= $this->translate('panel.tools.backup.size') ?></th>
                    <th class="text-align-center" style="width: 15%"><?= $this->translate('panel.tools.backup.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td class="truncate"><?= $this->icon('file-archive') ?> <a href="<?= $panel->uri('/backup/download/' . $backup['encodedName']) . '/' ?>"><?= $backup['name'] ?></a></td>
                        <td class="truncate text-align-center show-from-m"><?= $this->datetime($backup['timestamp']) ?></td>
                        <td class="truncate text-align-center show-from-s"><?= $backup['size'] ?></td>
                        <td class="text-align-center">
                            <button type="button" class="button-link" data-modal="deleteFileModal" data-modal-action="<?= $panel->uri('/backup/delete/' . $backup['encodedName']) . '/' ?>" title="<?= $this->translate('panel.tools.backup.delete') ?>" aria-label="<?= $this->translate('panel.tools.backup.delete') ?>"><?= $this->icon('trash') ?></button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
</div>

<template id="backups-row">
    <tr>
        <td class="truncate"><?= $this->icon('file-archive') ?> <a class="backup-uri" href=""></a></td>
        <td class="truncate text-align-center backup-date show-from-m"></td>
        <td class="truncate text-align-center backup-size show-from-s"></td>
        <td class="text-align-center">
            <button type="button" class="button-link backup-delete" data-modal="deleteFileModal" data-modal-action="" title="<?= $this->translate('panel.tools.backup.delete') ?>" aria-label="<?= $this->translate('panel.tools.backup.delete') ?>"><?= $this->icon('trash') ?></button>
        </td>
    </tr>
</template>
