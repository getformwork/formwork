<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\FileResponse;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use RuntimeException;

final class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    public function make(): JsonResponse|Response
    {
        if (!$this->hasPermission('panel.backup.make')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $backupper = $backupper = new Backupper([...$this->config->get('system.backup'), 'hostname' => $this->request->host()]);
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JsonResponse::error($this->translate('panel.backup.error.cannotMake', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
        }
        $filename = basename($file);
        $uriName = rawurlencode(base64_encode($filename));
        return JsonResponse::success($this->translate('panel.backup.ready'), data: [
            'filename'  => $filename,
            'uri'       => $this->panel->uri("/backup/download/{$uriName}/"),
            'date'      => Date::formatTimestamp(FileSystem::lastModifiedTime($file), $this->config->get('system.date.datetimeFormat'), $this->translations->getCurrent()),
            'size'      => FileSystem::formatSize(FileSystem::size($file)),
            'deleteUri' => $this->panel->uri("/backup/delete/{$uriName}/"),
            'maxFiles'  => $this->config->get('system.backup.maxFiles'),
        ]);
    }

    /**
     * Backup@download action
     */
    public function download(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.backup.download')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $file = FileSystem::joinPaths($this->config->get('system.backup.path'), basename(base64_decode((string) $routeParams->get('backup'))));
        try {
            if (FileSystem::isFile($file, assertExists: false)) {
                return new FileResponse($file, download: true);
            }
            throw new RuntimeException($this->translate('panel.backup.error.cannotDownload.invalidFilename'));
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate('panel.backup.error.cannotDownload', $this->translate($e->getLanguageString())), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->panel->panelRoot());
        }
    }

    /**
     * Backup@delete action
     */
    public function delete(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.backup.download')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $file = FileSystem::joinPaths($this->config->get('system.backup.path'), basename(base64_decode((string) $routeParams->get('backup'))));
        try {
            if (FileSystem::isFile($file, assertExists: false)) {
                FileSystem::delete($file);
                $this->panel->notify($this->translate('panel.backup.deleted'), 'success');
                return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->generateRoute('panel.index'));
            }
            throw new RuntimeException($this->translate('panel.backup.error.cannotDelete.invalidFilename'));
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate('panel.backup.error.cannotDelete', $this->translate($e->getLanguageString())), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->panel->panelRoot());
        }
    }
}
