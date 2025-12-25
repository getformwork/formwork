<?php

namespace Formwork\Panel\Controllers;

use Formwork\Config\Config;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

final class OptionsController extends AbstractController
{
    /**
     * All options tabs
     *
     * @var list<string>
     */
    private array $tabs = ['site', 'system'];

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

        // Set initial values on GET
        if ($this->request->method() === RequestMethod::GET) {
            $fields->setValues($this->config->get('system'))
                ->isValid(); // Pre-validate to populate validation state
        }

        $form = $this->form('system-options', $fields)
            ->processRequest($this->request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
            } else {
                $options = $this->getConfigOverrides()->get('system', []);
                $defaults = $this->getConfigDefaults()->get('system');

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
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('@panel.options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'system',
            ]),
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

        // Set initial values on GET
        if ($this->request->method() === RequestMethod::GET) {
            $fields->setValues($this->site->data())
                ->isValid(); // Pre-validate to populate validation state
        }

        $form = $this->form('site-options', $fields)
            ->processRequest($this->request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
            } else {
                $options = $this->getConfigOverrides()->get('site', []);
                $defaults = $this->getConfigDefaults()->get('site');
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
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('@panel.options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'site',
            ]),
            'fields' => $form->fields(),
        ]), $form->getResponseStatus());
    }

    /**
     * Get config defaults
     */
    private function getConfigDefaults(): Config
    {
        $config = new Config();
        $config->loadFromPath(SYSTEM_PATH . '/config/');
        $config->resolve([
            '%ROOT_PATH%'   => ROOT_PATH,
            '%SYSTEM_PATH%' => SYSTEM_PATH,
        ]);
        return $config;
    }

    /**
     * Get config overrides
     */
    private function getConfigOverrides(): Config
    {
        $config = new Config(resolved: true);
        $config->loadFromPath(ROOT_PATH . '/site/config/');
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

        // Update options with new values
        $options = Arr::exclude(
            Arr::override($options, Arr::undot($formData)),
            $defaults
        );

        // Update config file if options differ
        if ($options !== $old) {
            Yaml::encodeToFile($options, ROOT_PATH . "/site/config/{$type}.yaml");
            return true;
        }

        // Return false if options do not differ
        return false;
    }
}
