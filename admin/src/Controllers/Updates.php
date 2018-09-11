<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Updater;
use Formwork\Admin\Utils\JSONResponse;
use RuntimeException;

class Updates extends AbstractController
{
    public function check()
    {
        $updater = new Updater();
        $upToDate = $updater->checkUpdates();
        if ($upToDate) {
            JSONResponse::success($this->label('updates.status.up-to-date'), 200, array(
                'uptodate' => true
            ))->send();
        } else {
            JSONResponse::success($this->label('updates.status.found'), 200, array(
                'uptodate' => false,
                'release' => $updater->latestRelease()
            ))->send();
        }
    }

    public function update()
    {
        $updater = new Updater(array('force' => true));
        try {
            $updater->update();
            JSONResponse::success($this->label('updates.installed'), 200, array(
                'status' => $this->label('updates.status.up-to-date')
            ))->send();
        } catch (RuntimeException $e) {
            JSONResponse::error($this->label('updates.status.cannot-install'), 500, array(
                'status' => $this->label('updates.status.cannot-install')
            ))->send();
        }
    }
}
