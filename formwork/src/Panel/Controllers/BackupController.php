<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backupper;
use Formwork\Config\Config;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\FileResponse;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use RuntimeException;

class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(Config $config): JsonResponse|Response
    {
        if (!$this->hasPermission('backup.make')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $backupper = new Backupper($this->config);
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JsonResponse::error($this->translate('panel.backup.error.cannotMake', $e->getTranslatedMessage()), ResponseStatus::InternalServerError);
        }
        $filename = basename($file);
        $uriName = urlencode(base64_encode($filename));
        return JsonResponse::success($this->translate('panel.backup.ready'), data: [
            'filename'  => $filename,
            'uri'       => $this->panel()->uri('/backup/download/' . $uriName . '/'),
            'date'      => Date::formatTimestamp(FileSystem::lastModifiedTime($file), $config->get('system.date.datetimeFormat')),
            'size'      => FileSystem::formatSize(FileSystem::size($file)),
            'deleteUri' => $this->panel()->uri('/backup/delete/' . $uriName . '/'),
            'maxFiles'  => $config->get('system.backup.maxFiles'),
        ]);
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('backup.download')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $file = FileSystem::joinPaths($this->config->get('system.backup.path'), basename(base64_decode((string) $routeParams->get('backup'))));
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

    /**
     * Backup@delete action
     */
    public function delete(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('backup.download')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $file = FileSystem::joinPaths($this->config->get('system.backup.path'), basename(base64_decode((string) $routeParams->get('backup'))));
        try {
            if (FileSystem::isFile($file, assertExists: false)) {
                FileSystem::delete($file);
                $this->panel()->notify($this->translate('panel.backup.deleted'), 'success');
                return $this->redirectToReferer(default: '/dashboard/');
            }
            throw new RuntimeException($this->translate('panel.backup.error.cannotDelete.invalidFilename'));
        } catch (TranslatedException $e) {
            $this->panel()->notify($this->translate('panel.backup.error.cannotDelete', $e->getTranslatedMessage()), 'error');
            return $this->redirectToReferer(default: '/dashboard/');
        }
    }
}
