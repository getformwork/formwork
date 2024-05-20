<?php

namespace Formwork\Panel\Controllers;

use Formwork\Fields\FieldCollection;
use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Parsers\Json;
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
    protected array $tabs = ['site', 'system', 'info'];

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
                if ($this->site()->path() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site()->path());
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
                if ($this->site()->path() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site()->path());
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
     * Options@info action
     */
    public function info(): Response
    {
        $this->ensurePermission('options.info');

        $opcacheStatus = opcache_get_status(false) ?: [];

        $gdInfo = extension_loaded('gd') ? gd_info() : [];

        $dependencies = $this->getDependencies();

        $data = @[
            'PHP' => [
                'Version'             => PHP_VERSION,
                'Operating System'    => php_uname(),
                'Server API'          => PHP_SAPI,
                'Loaded php.ini'      => php_ini_loaded_file(),
                'Loaded Extensions'   => implode(', ', get_loaded_extensions()),
                'Zend Engine Version' => zend_version(),
            ],
            'HTTP Request Headers'  => $this->request->headers()->toArray(),
            'HTTP Response Headers' => $this->getHeaders(),
            'Server'                => [
                'IP Address'     => $_SERVER['SERVER_ADDR'],
                'Port'           => $_SERVER['SERVER_PORT'],
                'Name'           => $_SERVER['SERVER_NAME'],
                'Software'       => $_SERVER['SERVER_SOFTWARE'],
                'Apache Modules' => implode(', ', function_exists('apache_get_modules') ? apache_get_modules() : []),
                'Protocol'       => $_SERVER['SERVER_PROTOCOL'],
                'HTTPS'          => $this->request->isSecure() ? 'on' : 'off',
                'Request Time'   => gmdate('D, d M Y H:i:s T', $_SERVER['REQUEST_TIME']),
            ],
            'Client' => [
                'IP Address' => $this->request->ip(),
                'Port'       => $_SERVER['REMOTE_PORT'],
            ],
            'Session' => [
                'Session Cookie Lifetime' => ini_get('session.cookie_lifetime'),
                'Session Strict Mode'     => ini_get('session.use_strict_mode') ? 'true' : 'false',
            ],
            'Uploads' => [
                'File Uploads'         => ini_get('file_uploads') ? 'true' : 'false',
                'POST Max Size'        => ini_get('post_max_size'),
                'Maximum File Size'    => ini_get('upload_max_filesize'),
                'Maximum File Uploads' => ini_get('max_file_uploads'),
            ],
            'Script' => [
                'Max Execution Time' => ini_get('max_execution_time'),
                'Max Input Time'     => ini_get('max_input_time'),
                'Memory Limit'       => ini_get('memory_limit'),
                'Default MIME-Type'  => ini_get('default_mimetype'),
                'Default Charset'    => ini_get('default_charset'),
            ],
            'Streams' => [
                'Stream Wrappers' => implode(', ', stream_get_wrappers()),
                'Allow URL Fopen' => ini_get('allow_url_fopen') ? 'true' : 'false',
            ],
            'OPcache' => [
                'Enabled'                   => $opcacheStatus['opcache_enabled'] ? 'true' : 'false',
                'Cached Scripts'            => $opcacheStatus['opcache_statistics']['num_cached_scripts'] ?? 0,
                'Cache Hits'                => $opcacheStatus['opcache_statistics']['hits'] ?? 0,
                'Cache Misses'              => $opcacheStatus['opcache_statistics']['misses'] ?? 0,
                'Used Memory'               => $opcacheStatus['memory_usage']['used_memory'] ?? 0,
                'Free Memory'               => $opcacheStatus['memory_usage']['free_memory'] ?? 0,
                'Wasted Memory'             => $opcacheStatus['memory_usage']['wasted_memory'] ?? 0,
                'Current Wasted Percentage' => $opcacheStatus['memory_usage']['current_wasted_percentage'] ?? 0,
                'Max Wasted Percentage'     => ini_get('opcache.max_wasted_percentage'),
            ],
            'GD' => [
                'Version'            => $gdInfo['GD Version'] ?? '',
                'JPEG Support'       => $gdInfo['JPEG Support'] ?? '' ? 'true' : 'false',
                'PNG Support'        => $gdInfo['PNG Support'] ?? '' ? 'true' : 'false',
                'GIF Read Support'   => $gdInfo['GIF Read Support'] ?? '' ? 'true' : 'false',
                'GIF Create Support' => $gdInfo['GIF Create Support'] ?? '' ? 'true' : 'false',
                'WebP Support'       => $gdInfo['WebP Support'] ?? '' ? 'true' : 'false',
            ],
            'System' => [
                'Directory Separator' => DS,
                'EOL Symbol'          => addcslashes(PHP_EOL, "\r\n"),
                'Max Path Length'     => FileSystem::MAX_PATH_LENGTH,
                'File Creation Mask'  => sprintf('0%03o', umask()),
            ],
            'Formwork' => [
                'Formwork Version' => $this->app::VERSION,
                'Root Path'        => ROOT_PATH,
                'Formwork Path'    => SYSTEM_PATH,
                'Config Path'      => ROOT_PATH . '/site/config/',
                'Disk Usage'       => FileSystem::formatSize(FileSystem::directorySize(ROOT_PATH)),
            ],
            'Dependencies' => [
                'CommonMark Version'   => $dependencies['league/commonmark']['version'],
                'Symfony Yaml Version' => $dependencies['symfony/yaml']['version'],
            ],
        ];

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);

        return new Response($this->view('options.info', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'info',
            ]),
            'info' => $data,
        ]));
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            [$key, $value] = explode(':', $header, 2);
            $headers[$key] = trim($value);
        }
        return $headers;
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

    /**
     * Load dependencies data from composer.lock
     *
     * @return array<string, mixed>
     */
    protected function getDependencies(): array
    {
        $dependencies = [];
        if (FileSystem::exists(ROOT_PATH . '/composer.lock')) {
            $composerLock = Json::parseFile(ROOT_PATH . '/composer.lock');
            foreach ($composerLock['packages'] as $package) {
                /**
                 * @var string
                 */
                $packageName = $package['name'];
                $dependencies[$packageName] = $package;
            }
        }
        return $dependencies;
    }
}
