<?php

namespace Formwork\Panel\Controllers;

use Formwork\Fields\FieldCollection;
use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

class OptionsController extends AbstractController
{
    /**
     * All options tabs
     *
     * @var list<string>
     */
    protected array $tabs = ['site', 'system'];

    /**
     * Options@index action
     */
    public function index(): RedirectResponse
    {
        $this->ensurePermission('options.site');
        return $this->redirect($this->generateRoute('panel.options.site'));
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(Schemes $schemes): Response
    {
        $this->ensurePermission('options.system');

        $scheme = $schemes->get('config.system');
        $fields = $scheme->fields();

        if ($this->request->method() === RequestMethod::POST) {
            $data = $this->request->input();
            $options = $this->config->get('system');
            $defaults = $this->app->defaults();
            $fields->setValues($data, null)->validate();

            $differ = $this->updateOptions('system', $fields, $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                if ($this->site()->contentPath() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site()->contentPath());
            }

            $this->panel()->notify($this->translate('panel.options.updated'), 'success');
            return $this->redirect($this->generateRoute('panel.options.system'));
        }

        $fields->setValues($this->config->get('system'));

        $this->modal('changes');

        return new Response($this->view('options.system', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'system',
            ]),
            'fields' => $fields,
        ]));
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(Schemes $schemes): Response
    {
        $this->ensurePermission('options.site');

        $scheme = $schemes->get('config.site');
        $fields = $scheme->fields();

        if ($this->request->method() === RequestMethod::POST) {
            $data = $this->request->input();
            $options = $this->site()->data();
            $defaults = $this->site()->defaults();
            $fields->setValues($data, null)->validate();
            $differ = $this->updateOptions('site', $fields, $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                if ($this->site()->contentPath() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site()->contentPath());
            }

            $this->panel()->notify($this->translate('panel.options.updated'), 'success');
            return $this->redirect($this->generateRoute('panel.options.site'));
        }

        $fields->setValues($this->site()->data());

        $this->modal('changes');

        return new Response($this->view('options.site', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'site',
            ]),
            'fields' => $fields,
        ]));
    }

    /**
     * Update options of a given type with given data
     *
     * @param 'site'|'system'      $type
     * @param array<string, mixed> $options
     * @param array<string, mixed> $defaults
     */
    protected function updateOptions(string $type, FieldCollection $fieldCollection, array $options, array $defaults): bool
    {
        $old = $options;
        $options = [];

        // Update options with new values
        foreach ($fieldCollection as $field) {
            // Ignore empty and default values
            if ($field->isEmpty()) {
                continue;
            }
            if (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value()) {
                continue;
            }
            Arr::set($options, $field->name(), $field->value());
        }

        // Update config file if options differ
        if ($options !== $old) {
            Yaml::encodeToFile($options, ROOT_PATH . '/site/config/' . $type . '.yaml');
            return true;
        }

        // Return false if options do not differ
        return false;
    }
}
