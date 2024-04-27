<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backupper;
use Formwork\Data\Collection;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class ToolsController extends AbstractController
{
    /**
     * All options tabs
     *
     * @var list<string>
     */
    protected array $tabs = ['backups', 'updates'];

    /**
     * Tools@index action
     */
    public function index(): Response
    {
        $this->ensurePermission('tools.backups');
        return $this->redirect($this->generateRoute('panel.tools.backups'));
    }

    /**
     * Tools@backups action
     */
    public function backups(RouteParams $routeParams): Response
    {
        $this->ensurePermission('tools.backups');

        $backupper = new Backupper($this->config);

        $backups = Arr::map($backupper->getBackups(), fn (string $path, int $timestamp): array => [
            'name'        => basename($path),
            'encodedName' => urlencode(base64_encode(basename($path))),
            'timestamp'   => $timestamp,
            'size'        => FileSystem::formatSize(FileSystem::size($path)),
        ]);

        $this->modal('deleteFile');

        return new Response($this->view('tools.backups', [
            'title' => $this->translate('panel.tools.backups'),
            'tabs'  => $this->view('tools.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'backups',
            ]),
            'backups' => Collection::from($backups),
        ]));
    }

    /**
     * Tools@updates action
     */
    public function updates(): Response
    {
        $this->ensurePermission('tools.updates');

        return new Response($this->view('tools.updates', [
            'title' => $this->translate('panel.tools.updates'),
            'tabs'  => $this->view('tools.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'updates',
            ]),
            'currentVersion' => $this->app::VERSION,
        ]));
    }
}
