<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Backupper;
use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPResponse;
use RuntimeException;

class Backup extends AbstractController
{
    public function make()
    {
        $this->ensurePermission('backup.make');
        $backupper = new Backupper();
        try {
            $file = $backupper->backup();
            $filename = FileSystem::basename($file);
            JSONResponse::success($this->label('backup.ready'), 200, array(
                'filename' => $filename,
                'uri' => $this->uri('/backup/download/' . urlencode(base64_encode($filename)) . '/')
            ))->send();
        } catch (LocalizedException $e) {
            JSONResponse::error($this->label('backup.error.cannot-make', $e->getLocalizedMessage()), 500)->send();
        }
    }

    public function download(RouteParams $params)
    {
        $this->ensurePermission('backup.download');
        $file = $this->option('backup.path') . base64_decode($params->get('backup'));
        try {
            if (FileSystem::exists($file) && FileSystem::isFile($file)) {
                HTTPResponse::download($file);
            } else {
                throw new RuntimeException($this->label('backup.error.cannot-download.invalid-filename'));
            }
        } catch (LocalizedException $e) {
            $this->notify($this->label('backup.error.cannot-download', $e->getLocalizedMessage()), 'error');
            $this->redirectToReferer(302, true, '/dashboard/');
        }
    }
}
