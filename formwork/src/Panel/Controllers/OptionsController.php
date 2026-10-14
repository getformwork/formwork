<?php

namespace Formwork\Panel\Controllers;

use Formwork\Config\Config;
use Formwork\Http\Response;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

final class OptionsController extends AbstractController
{
    /**
     * Options@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('panel.options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return $this->redirect($this->generateRoute('panel.options.site'));
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('panel.options.system')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.system');
        $fields = $scheme->fields();

        $fields->setValues($this->config->getArray('system'));

        $form = $this->form('system-options', $fields)
            ->processRequest($this->request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
            } else {
                $options = $this->getConfigOverrides()->getArray('system', []);
                $defaults = $this->getConfigDefaults()->getArray('system');

                $differ = $this->updateOptions('system', $form->data()->toArray(), $options, $defaults);

                // Touch content folder to invalidate cache
                if ($differ) {
                    if ($this->site->contentPath() === null) {
                        throw new UnexpectedValueException('Unexpected missing site path');
                    }
                    FileSystem::touch($this->site->contentPath());
                }

                $this->panel->notify($this->translate('panel.options.updated'), 'success');
                return $this->redirect($this->generateRoute('panel.options.system'));
            }
        }

        return new Response($this->view('@panel.options.system', [
            'title'  => $this->translate('panel.options.options'),
            'fields' => $form->fields(),
        ]), $form->getResponseStatus());
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('panel.options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.site');
        $fields = $scheme->fields();

        $fields->setValues($this->site->data());

        $form = $this->form('site-options', $fields)
            ->processRequest($this->request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
            } else {
                $options = $this->getConfigOverrides()->getArray('site', []);
                $defaults = $this->getConfigDefaults()->getArray('site');
                $differ = $this->updateOptions('site', $form->data()->toArray(), $options, $defaults);

                // Touch content folder to invalidate cache
                if ($differ) {
                    if ($this->site->contentPath() === null) {
                        throw new UnexpectedValueException('Unexpected missing site path');
                    }
                    FileSystem::touch($this->site->contentPath());
                }

                $this->panel->notify($this->translate('panel.options.updated'), 'success');
                return $this->redirect($this->generateRoute('panel.options.site'));
            }
        }

        return new Response($this->view('@panel.options.site', [
            'title'  => $this->translate('panel.options.options'),
            'fields' => $form->fields(),
        ]), $form->getResponseStatus());
    }

    /**
     * Get config defaults
     */
    private function getConfigDefaults(): Config
    {
        $config = new Config();
        $config->loadFromPath($this->app->systemPath() . '/config/');
        $config->resolve([
            '%ROOT_PATH%'   => $this->app->rootPath(),
            '%SYSTEM_PATH%' => $this->app->systemPath(),
        ]);
        return $config;
    }

    /**
     * Get config overrides
     */
    private function getConfigOverrides(): Config
    {
        $config = new Config(resolved: true);
        $config->loadFromPath($this->app->rootPath() . '/site/config/');
        return $config;
    }

    /**
     * Update options of a given type with given data
     *
     * @param 'site'|'system'      $type
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $options
     * @param array<string, mixed> $defaults
     */
    private function updateOptions(string $type, array $formData, array $options, array $defaults): bool
    {
        $old = $options;

        // Replace with form data so removed array keys are not retained
        foreach ($formData as $key => $value) {
            Arr::set($options, $key, $value);
        }

        // Keep only the overrides
        $options = Arr::exclude($options, $defaults);

        // Update config file if options differ
        if ($options !== $old) {
            Yaml::encodeToFile($options, $this->app->rootPath() . "/site/config/{$type}.yaml");
            return true;
        }

        // Return false if options do not differ
        return false;
    }
}
