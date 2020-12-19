<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Backupper;
use Formwork\Admin\Updater;
use Formwork\Utils\JSONResponse;
use Formwork\Core\Formwork;
use Formwork\Exceptions\TranslatedException;
use RuntimeException;

class Updates extends AbstractController
{
    /**
     * Updates@check action
     */
    public function check(): void
    {
        $this->ensurePermission('updates.check');
        $updater = new Updater(['preferDistAssets' => true]);
        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException $e) {
            JSONResponse::error($this->admin()->translate('updates.status.cannot-check'), 500, [
                'status' => $this->admin()->translate('updates.status.cannot-check')
            ])->send();
        }
        if ($upToDate) {
            JSONResponse::success($this->admin()->translate('updates.status.up-to-date'), 200, [
                'uptodate' => true
            ])->send();
        } else {
            JSONResponse::success($this->admin()->translate('updates.status.found'), 200, [
                'uptodate' => false,
                'release'  => $updater->latestRelease()
            ])->send();
        }
    }

    /**
     * Updates@update action
     */
    public function update(): void
    {
        $this->ensurePermission('updates.update');
        $updater = new Updater(['force' => true, 'preferDistAssets' => true, 'cleanupAfterInstall' => true]);
        if (Formwork::instance()->config()->get('updates.backup_before')) {
            $backupper = new Backupper();
            try {
                $backupper->backup();
            } catch (TranslatedException $e) {
                JSONResponse::error($this->admin()->translate('updates.status.cannot-make-backup'), 500, [
                    'status' => $this->admin()->translate('updates.status.cannot-make-backup')
                ])->send();
            }
        }
        try {
            $updater->update();
        } catch (RuntimeException $e) {
            JSONResponse::error($this->admin()->translate('updates.status.cannot-install'), 500, [
                'status' => $this->admin()->translate('updates.status.cannot-install')
            ])->send();
        }
        if (Formwork::instance()->config()->get('cache.enabled')) {
            Formwork::instance()->cache()->clear();
        }
        JSONResponse::success($this->admin()->translate('updates.installed'), 200, [
            'status' => $this->admin()->translate('updates.status.up-to-date')
        ])->send();
    }
}
