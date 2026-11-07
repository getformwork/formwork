<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\FileResponse;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Users\Attributes\Permission;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;

final class BackupController extends AbstractController
{
    /**
     * Backup@make action
     */
    #[Permission('panel.backup.make')]
    public function make(Backupper $backupper): JsonResponse|Response
    {
        try {
            $file = $backupper->backup();
        } catch (TranslatedException $e) {
            return JsonResponse::error($this->translate('panel.backup.error.cannotMake', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
        }
        $filename = Path::basename($file);
        $uriName = rawurlencode(base64_encode($filename));
        return JsonResponse::success($this->translate('panel.backup.ready'), data: [
            'filename'  => $filename,
            'uri'       => $this->app->uri()->route('panel.backup.download', ['backup' => $uriName]),
            'date'      => Date::formatTimestamp(FileSystem::lastModifiedTime($file), $this->config->getString('system.date.datetimeFormat'), $this->translations->getCurrent()),
            'size'      => FileSystem::formatSize(FileSystem::size($file)),
            'deleteUri' => $this->app->uri()->route('panel.backup.delete', ['backup' => $uriName]),
            'maxFiles'  => $this->config->getInt('system.backup.maxFiles'),
        ]);
    }

    /**
     * Backup@download action
     */
    #[Permission('panel.backup.download')]
    public function download(RouteParams $routeParams): Response
    {
        try {
            $backupPath = rtrim(Path::normalize($this->config->getString('system.backup.path')), '/');
            $file = FileSystem::joinPaths($backupPath, base64_decode((string) $routeParams->get('backup')));
            if (Path::dirname($file) === $backupPath && FileSystem::isFile($file, assertExists: false)) {
                return new FileResponse($file, download: true);
            }
            throw new TranslatedException('Invalid backup filename', 'panel.backup.error.cannotDownload.invalidFilename');
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate('panel.backup.error.cannotDownload', $this->translate($e->getLanguageString())), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->panel->panelRoot());
        }
    }

    /**
     * Backup@delete action
     */
    #[Permission('panel.backup.delete')]
    public function delete(RouteParams $routeParams): RedirectResponse
    {
        try {
            $backupPath = rtrim(Path::normalize($this->config->getString('system.backup.path')), '/');
            $file = FileSystem::joinPaths($backupPath, base64_decode((string) $routeParams->get('backup')));
            if (Path::dirname($file) === $backupPath && FileSystem::isFile($file, assertExists: false)) {
                FileSystem::delete($file);
                $this->panel->notify($this->translate('panel.backup.deleted'), 'success');
                return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->generateRoute('panel.index'));
            }
            throw new TranslatedException('Invalid backup filename', 'panel.backup.error.cannotDelete.invalidFilename');
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate('panel.backup.error.cannotDelete', $this->translate($e->getLanguageString())), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.tools.backups'), base: $this->panel->panelRoot());
        }
    }
}
