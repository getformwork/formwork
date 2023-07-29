<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backupper;
use Formwork\Cache\AbstractCache;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Updater;
use RuntimeException;

class UpdatesController extends AbstractController
{
    /**
     * Updates@check action
     */
    public function check(Updater $updater): JsonResponse
    {
        $this->ensurePermission('updates.check');
        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException $e) {
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
    public function update(Updater $updater, AbstractCache $cache): JsonResponse
    {
        $this->ensurePermission('updates.update');
        if ($this->config->get('system.updates.backupBefore')) {
            $backupper = new Backupper($this->config);
            try {
                $backupper->backup();
            } catch (TranslatedException $e) {
                return JsonResponse::error($this->translate('panel.updates.status.cannotMakeBackup'), ResponseStatus::InternalServerError, [
                    'status' => $this->translate('panel.updates.status.cannotMakeBackup'),
                ]);
            }
        }
        try {
            $updater->update();
        } catch (RuntimeException $e) {
            return JsonResponse::error($this->translate('panel.updates.status.cannotInstall'), ResponseStatus::InternalServerError, [
                'status' => $this->translate('panel.updates.status.cannotInstall'),
            ]);
        }
        if ($this->config->get('system.cache.enabled')) {
            $cache->clear();
        }
        return JsonResponse::success($this->translate('panel.updates.installed'), data: [
            'status' => $this->translate('panel.updates.status.upToDate'),
        ]);
    }
}
