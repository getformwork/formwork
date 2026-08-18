<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Cache\AbstractCache;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Updater\Updater;
use RuntimeException;

final class UpdatesController extends AbstractController
{
    /**
     * Updates@check action
     */
    public function check(Updater $updater): JsonResponse|Response
    {
        if (!$this->hasPermission('panel.updates.check')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException) {
            return JsonResponse::error($this->translate('panel.updates.status.cannotCheck'), ResponseStatus::InternalServerError, [
                'status' => $this->translate('panel.updates.status.cannotCheck'),
            ]);
        }
        if ($upToDate) {
            return JsonResponse::success($this->translate('panel.updates.status.upToDate'), data: [
                'uptodate' => true,
            ]);
        }
        return JsonResponse::success($this->translate('panel.updates.status.found'), data: [
            'uptodate' => false,
            'release'  => $updater->latestRelease(),
        ]);
    }

    /**
     * Updates@update action
     */
    public function update(Updater $updater, AbstractCache $cache): JsonResponse|Response
    {
        if (!$this->hasPermission('panel.updates.update')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        if ($this->config->getBool('system.updates.backupBefore')) {
            $backupper = new Backupper([...$this->config->getArray('system.backup'), 'hostname' => $this->request->host()]);
            try {
                $backupper->backup();
            } catch (TranslatedException) {
                return JsonResponse::error($this->translate('panel.updates.status.cannotMakeBackup'), ResponseStatus::InternalServerError, [
                    'status' => $this->translate('panel.updates.status.cannotMakeBackup'),
                ]);
            }
        }
        try {
            $updater->update();
        } catch (RuntimeException) {
            return JsonResponse::error($this->translate('panel.updates.status.cannotInstall'), ResponseStatus::InternalServerError, [
                'status' => $this->translate('panel.updates.status.cannotInstall'),
            ]);
        }
        if ($this->config->getBool('system.cache.enabled')) {
            $cache->clear();
        }
        return JsonResponse::success($this->translate('panel.updates.installed'), data: [
            'status' => $this->translate('panel.updates.status.upToDate'),
        ]);
    }
}
