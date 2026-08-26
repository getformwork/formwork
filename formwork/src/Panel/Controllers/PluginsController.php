<?php

namespace Formwork\Panel\Controllers;

use Formwork\Fields\FieldCollection;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Parsers\Yaml;
use Formwork\Plugins\Exceptions\PluginInitializationException;
use Formwork\Plugins\Plugin;
use Formwork\Plugins\Plugins;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;

/**
 * @since 2.3.0
 */
final class PluginsController extends AbstractController
{
    /**
     * Plugins@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('panel.plugins')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        if ($this->app->plugins()->isEmpty()) {
            return $this->forward(ErrorsController::class, 'notFound');
        }

        return new Response($this->view('@panel.plugins.index', [
            'title'   => $this->translate('panel.plugins.plugins'),
            'plugins' => $this->app->plugins()->sortBy('manifest.title'),
        ]));
    }

    /**
     * Plugins@plugin action
     */
    public function plugin(RouteParams $routeParams, Plugins $plugins): Response
    {
        if (!$this->hasPermission('panel.plugins')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $name = Str::toCamelCase($routeParams->get('id'));

        if (($plugin = $plugins->get($name)) === null) {
            return $this->forward(ErrorsController::class, 'notFound');
        }

        $scheme = $this->getPluginScheme($plugin);

        // If no scheme, just show plugin info
        if ($scheme === null) {
            return new Response($this->view('@panel.plugins.plugin', [
                'title'  => $plugin->manifest()->title() ?? $plugin->name(),
                'plugin' => $plugin,
                'fields' => new FieldCollection(),
                ...$this->getPreviousAndNextPlugin($plugin),
            ]));
        }

        $fields = $scheme->fields();

        $fields->setValues($this->config->getArray("plugins.{$name}", []));

        $form = $this->form('plugin-options', $fields)
            ->processRequest($this->request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.plugins.plugin.cannotSave.invalidFields'), 'error');
            } else {
                $this->updatePluginsOptions($plugin, $form->data()->toArray());
                $this->panel->notify($this->translate('panel.plugins.plugin.saved'), 'success');
                return $this->redirect($this->generateRoute('panel.plugins.plugin', $routeParams->toArray()));
            }
        }

        return new Response($this->view('@panel.plugins.plugin', [
            'title'  => $plugin->manifest()->title() ?? $plugin->name(),
            'plugin' => $plugin,
            'fields' => $form->fields(),
            ...$this->getPreviousAndNextPlugin($plugin),
        ]), $form->getResponseStatus());
    }

    /**
     * Plugins@enable action
     */
    public function enable(RouteParams $routeParams, Plugins $plugins): Response
    {
        if (!$this->hasPermission('panel.plugins')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $name = Str::toCamelCase($routeParams->get('id'));

        if (($plugin = $plugins->get($name)) === null) {
            return JsonResponse::error($this->translate('panel.plugins.plugin.notFound'), ResponseStatus::NotFound);
        }

        $this->togglePluginStatus($plugin, true);

        try {
            $plugins->initialize($name);
        } catch (PluginInitializationException) {
            $this->togglePluginStatus($plugin, false);
            $this->panel->notify($this->translate('panel.plugins.plugin.cannotEnable.initializationError'), 'error');
            return JsonResponse::error($this->translate('panel.plugins.plugin.cannotEnable.initializationError'), ResponseStatus::InternalServerError);
        }

        $this->panel->notify($this->translate('panel.plugins.plugin.enabled'), 'success');
        return JsonResponse::success($this->translate('panel.plugins.plugin.enabled'));
    }

    /**
     * Plugins@disable action
     */
    public function disable(RouteParams $routeParams, Plugins $plugins): Response
    {
        if (!$this->hasPermission('panel.plugins')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $name = Str::toCamelCase($routeParams->get('id'));

        if (($plugin = $plugins->get($name)) === null) {
            return JsonResponse::error($this->translate('panel.plugins.plugin.notFound'), ResponseStatus::NotFound);
        }

        $this->togglePluginStatus($plugin, false);

        $this->panel->notify($this->translate('panel.plugins.plugin.disabled'), 'success');
        return JsonResponse::success($this->translate('panel.plugins.plugin.disabled'));
    }

    private function togglePluginStatus(Plugin $plugin, bool $enabled): void
    {
        $this->updatePluginsOptions($plugin, ['enabled' => $enabled]);
    }

    /**
     * Update plugin options in its config file
     *
     * @param array<string, mixed> $options
     */
    private function updatePluginsOptions(Plugin $plugin, array $options): void
    {
        $options = Arr::override($this->config->getArray("plugins.{$plugin->name()}", []), Arr::undot($options));

        if (!FileSystem::isDirectory(ROOT_PATH . '/site/config/plugins/', assertExists: false)) {
            FileSystem::createDirectory(ROOT_PATH . '/site/config/plugins/');
        }

        $path = FileSystem::joinPaths(ROOT_PATH . '/site/config/plugins', Path::resolve("{$plugin->id()}.yaml", '/', DIRECTORY_SEPARATOR));
        Yaml::encodeToFile($options, $path);
    }

    /**
     * Get scheme for a given plugin
     */
    private function getPluginScheme(Plugin $plugin): ?Scheme
    {
        $id = $plugin->id();

        $schemes = $this->app->schemes();

        if ($schemes->has("plugins.{$id}")) {
            return $schemes->get("plugins.{$id}");
        }

        // Try to load scheme from plugin path
        $path = FileSystem::joinPaths($plugin->path(), "schemes/plugins/{$id}.yaml");

        if (FileSystem::exists($path)) {
            $schemes->load("plugins.{$id}", $path);
            return $schemes->get("plugins.{$id}");
        }

        return null;
    }

    /**
     * Get previous and next plugin of a given plugin
     *
     * @return array{previousPlugin: ?Plugin, nextPlugin: ?Plugin}
     */
    private function getPreviousAndNextPlugin(Plugin $plugin): array
    {
        $plugins = $this->app->plugins()->sortBy('manifest.title');

        $pluginIndex = $plugins->indexOf($plugin);

        return [
            'previousPlugin' => $plugins->nth($pluginIndex - 1),
            'nextPlugin'     => $plugins->nth($pluginIndex + 1),
        ];
    }
}
