<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backupper;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\FileResponse;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use RuntimeException;

class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(): JsonResponse
    {
        $this->ensurePermission('backup.make');
        $backupper = new Backupper($this->config);
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JsonResponse::error($this->translate('panel.backup.error.cannotMake', $e->getTranslatedMessage()), ResponseStatus::InternalServerError);
        }
        $filename = basename($file);
        return JsonResponse::success($this->translate('panel.backup.ready'), data: [
            'filename' => $filename,
            'uri'      => $this->panel()->uri('/backup/download/' . urlencode(base64_encode($filename)) . '/'),
        ]);
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $params): Response
    {
        $this->ensurePermission('backup.download');
        $file = FileSystem::joinPaths($this->config->get('system.backup.path'), basename(base64_decode($params->get('backup'))));
        try {
            if (FileSystem::isFile($file, assertExists: false)) {
                return new FileResponse($file, download: true);
            }
            throw new RuntimeException($this->translate('panel.backup.error.cannotDownload.invalidFilename'));
        } catch (TranslatedException $e) {
            $this->panel()->notify($this->translate('panel.backup.error.cannotDownload', $e->getTranslatedMessage()), 'error');
            return $this->redirectToReferer(default: '/dashboard/');
        }
    }
}
