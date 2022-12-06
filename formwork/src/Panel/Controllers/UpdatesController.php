<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Formwork;
use Formwork\Panel\Backupper;
use Formwork\Panel\Updater;
use Formwork\Response\JSONResponse;
use RuntimeException;

class UpdatesController extends AbstractController
{
    /**
     * Updates@check action
     */
    public function check(): JSONResponse
    {
        $this->ensurePermission('updates.check');
        $updater = new Updater(['preferDistAssets' => true]);
        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException $e) {
            return JSONResponse::error($this->translate('panel.updates.status.cannotCheck'), 500, [
                'status' => $this->translate('panel.updates.status.cannotCheck')
            ]);
        }
        if ($upToDate) {
            return JSONResponse::success($this->translate('panel.updates.status.upToDate'), 200, [
                'uptodate' => true
            ]);
        }
        return JSONResponse::success($this->translate('panel.updates.status.found'), 200, [
            'uptodate' => false,
            'release'  => $updater->latestRelease()
        ]);
    }

    /**
     * Updates@update action
     */
    public function update(): JSONResponse
    {
        $this->ensurePermission('updates.update');
        $updater = new Updater(['force' => true, 'preferDistAssets' => true, 'cleanupAfterInstall' => true]);
        if (Formwork::instance()->config()->get('updates.backupBefore')) {
            $backupper = new Backupper();
            try {
                $backupper->backup();
            } catch (TranslatedException $e) {
                return JSONResponse::error($this->translate('panel.updates.status.cannotMakeBackup'), 500, [
                    'status' => $this->translate('panel.updates.status.cannotMakeBackup')
                ]);
            }
        }
        try {
            $updater->update();
        } catch (RuntimeException $e) {
            return JSONResponse::error($this->translate('panel.updates.status.cannotInstall'), 500, [
                'status' => $this->translate('panel.updates.status.cannotInstall')
            ]);
        }
        if (Formwork::instance()->config()->get('cache.enabled')) {
            Formwork::instance()->cache()->clear();
        }
        return JSONResponse::success($this->translate('panel.updates.installed'), 200, [
            'status' => $this->translate('panel.updates.status.upToDate')
        ]);
    }
}
