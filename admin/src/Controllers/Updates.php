<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Backupper;
use Formwork\Admin\Exceptions\TranslatedException;
use Formwork\Admin\Updater;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Core\Formwork;
use RuntimeException;

class Updates extends AbstractController
{
    /**
     * Updates@check action
     */
    public function check()
    {
        $this->ensurePermission('updates.check');
        $updater = new Updater();
        $upToDate = $updater->checkUpdates();
        if ($upToDate) {
            JSONResponse::success($this->label('updates.status.up-to-date'), 200, array(
                'uptodate' => true
            ))->send();
        } else {
            JSONResponse::success($this->label('updates.status.found'), 200, array(
                'uptodate' => false,
                'release'  => $updater->latestRelease()
            ))->send();
        }
    }

    /**
     * Updates@update action
     */
    public function update()
    {
        $this->ensurePermission('updates.update');
        $updater = new Updater(array('force' => true));
        if ($this->option('updates.backup_before')) {
            $backupper = new Backupper();
            try {
                $backupper->backup();
            } catch (TranslatedException $e) {
                JSONResponse::error($this->label('updates.status.cannot-make-backup'), 500, array(
                    'status' => $this->label('updates.status.cannot-make-backup')
                ))->send();
            }
        }
        try {
            $updater->update();
        } catch (RuntimeException $e) {
            JSONResponse::error($this->label('updates.status.cannot-install'), 500, array(
                'status' => $this->label('updates.status.cannot-install')
            ))->send();
        }
        if (!is_null(Formwork::instance()->cache())) {
            Formwork::instance()->cache()->clear();
        }
        JSONResponse::success($this->label('updates.installed'), 200, array(
            'status' => $this->label('updates.status.up-to-date')
        ))->send();
    }
}
