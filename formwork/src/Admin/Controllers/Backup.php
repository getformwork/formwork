<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Backupper;
use Formwork\Admin\Exceptions\TranslatedException;
use Formwork\Utils\JSONResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPResponse;
use RuntimeException;

class Backup extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(): void
    {
        $this->ensurePermission('backup.make');
        $backupper = new Backupper();
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            JSONResponse::error($this->label('backup.error.cannot-make', $e->getTranslatedMessage()), 500)->send();
        }
        $filename = basename($file);
        JSONResponse::success($this->label('backup.ready'), 200, [
            'filename' => $filename,
            'uri'      => $this->uri('/backup/download/' . urlencode(base64_encode($filename)) . '/')
        ])->send();
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $params): void
    {
        $this->ensurePermission('backup.download');
        $file = Formwork::instance()->config()->get('backup.path') . base64_decode($params->get('backup'));
        try {
            if (FileSystem::isFile($file, false)) {
                HTTPResponse::download($file);
            } else {
                throw new RuntimeException($this->label('backup.error.cannot-download.invalid-filename'));
            }
        } catch (TranslatedException $e) {
            $this->notify($this->label('backup.error.cannot-download', $e->getTranslatedMessage()), 'error');
            $this->redirectToReferer(302, '/dashboard/');
        }
    }
}
