<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Parsers\Yaml;
use Formwork\Plugins\Exceptions\PluginInitializationException;
use Formwork\Plugins\Plugin;
use Formwork\Plugins\Plugins;
use Formwork\Router\RouteParams;
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

        $fields = $plugin->scheme()->fields();

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
            'fields' => $form->fields()->filterBy('name', '!==', 'enabled'),
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

        $fields = $plugin->scheme()->fields();

        $fields->setValues($this->config->getArray("plugins.{$name}", []));

        if (!$fields->isValid()) {
            return JsonResponse::error($this->translate('panel.plugins.plugin.cannotSave.invalidFields'), ResponseStatus::UnprocessableEntity);
        }

        $this->togglePluginStatus($plugin, true);

        try {
            $plugins->initialize($name);
        } catch (PluginInitializationException) {
            $this->togglePluginStatus($plugin, false);
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
     * @param array<string, mixed> $data
     */
    private function updatePluginsOptions(Plugin $plugin, array $data): void
    {
        $options = $this->config->getArray("plugins.{$plugin->name()}", []);

        // Replace with form data so removed array keys are not retained
        foreach ($data as $key => $value) {
            Arr::set($options, $key, $value);
        }

        // Keep only the overrides of the plugin manifest defaults
        $options = Arr::exclude($options, Arr::undot($plugin->manifest()->config()));

        $configPath = $this->app->rootPath() . '/site/config/plugins';

        if (!FileSystem::isDirectory($configPath . '/', assertExists: false)) {
            FileSystem::createDirectory($configPath . '/');
        }

        $path = FileSystem::joinPaths($configPath, Path::resolve("{$plugin->id()}.yaml", '/', DIRECTORY_SEPARATOR));
        Yaml::encodeToFile($options, $path);
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
