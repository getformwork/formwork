<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Formwork;
use Formwork\Panel\Backupper;
use Formwork\Response\FileResponse;
use Formwork\Response\JSONResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use RuntimeException;

class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(): JSONResponse
    {
        $this->ensurePermission('backup.make');
        $backupper = new Backupper();
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JSONResponse::error($this->translate('panel.backup.error.cannot-make', $e->getTranslatedMessage()), 500);
        }
        $filename = basename($file);
        return JSONResponse::success($this->translate('panel.backup.ready'), 200, [
            'filename' => $filename,
            'uri'      => $this->panel()->uri('/backup/download/' . urlencode(base64_encode($filename)) . '/')
        ]);
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $params): Response
    {
        $this->ensurePermission('backup.download');
        $file = Formwork::instance()->config()->get('backup.path') . basename(base64_decode($params->get('backup')));
        try {
            if (FileSystem::isFile($file, false)) {
                return new FileResponse($file, true);
            }
            throw new RuntimeException($this->translate('panel.backup.error.cannot-download.invalid-filename'));
        } catch (TranslatedException $e) {
            $this->panel()->notify($this->translate('panel.backup.error.cannot-download', $e->getTranslatedMessage()), 'error');
            return $this->redirectToReferer(302, '/dashboard/');
        }
    }
}
