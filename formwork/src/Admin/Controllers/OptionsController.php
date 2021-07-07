<?php

namespace Formwork\Admin\Controllers;

use Formwork\Data\DataGetter;
use Formwork\Fields\Fields;
use Formwork\Fields\Validator;
use Formwork\Formwork;
use Formwork\Parsers\JSON;
use Formwork\Parsers\YAML;
use Formwork\Response\RedirectResponse;
use Formwork\Response\Response;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;

class OptionsController extends AbstractController
{
    /**
     * All options tabs
     */
    protected array $tabs = ['system', 'site', 'updates', 'info'];

    /**
     * Options@index action
     */
    public function index(): RedirectResponse
    {
        $this->ensurePermission('options.system');
        return $this->admin()->redirect('/options/system/');
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(): Response
    {
        $this->ensurePermission('options.system');

        $fields = new Fields(Formwork::instance()->schemes()->get('config', 'system')->get('fields'));

        if (HTTPRequest::method() === 'POST') {
            $data = HTTPRequest::postData();
            $options = Formwork::instance()->config();
            $defaults = Formwork::instance()->defaults();
            $differ = $this->updateOptions('system', $fields->validate($data), $options->toArray(), $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch(Formwork::instance()->config()->get('content.path'));
            }

            $this->admin()->notify($this->admin()->translate('admin.options.updated'), 'success');
            return $this->admin()->redirect('/options/system/');
        }

        $fields->validate(Formwork::instance()->config());

        $this->modal('changes');

        return new Response($this->view('options.system', [
            'title'   => $this->admin()->translate('admin.options.options'),
            'tabs'    => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'system'
            ], true),
            'fields' => $fields
        ], true));
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(): Response
    {
        $this->ensurePermission('options.site');

        $fields = new Fields(Formwork::instance()->schemes()->get('config', 'site')->get('fields'));

        if (HTTPRequest::method() === 'POST') {
            $data = HTTPRequest::postData();
            $options = $this->site()->data();
            $defaults = Formwork::instance()->site()->defaults();
            $differ = $this->updateOptions('site', $fields->validate($data), $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch(Formwork::instance()->config()->get('content.path'));
            }

            $this->admin()->notify($this->admin()->translate('admin.options.updated'), 'success');
            return $this->admin()->redirect('/options/site/');
        }

        $fields->validate(new DataGetter($this->site()->data()));

        $this->modal('changes');

        return new Response($this->view('options.site', [
            'title'   => $this->admin()->translate('admin.options.options'),
            'tabs'    => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'site'
            ], true),
            'fields' => $fields
        ], true));
    }

    /**
     * Options@updates action
     */
    public function updates(): Response
    {
        $this->ensurePermission('options.updates');

        return new Response($this->view('options.updates', [
            'title'   => $this->admin()->translate('admin.options.updates'),
            'tabs'    => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'updates'
            ], true),
            'currentVersion' => Formwork::VERSION
        ], true));
    }

    /**
     * Options@info action
     */
    public function info(): Response
    {
        $this->ensurePermission('options.info');

        $opcacheStatus = ini_get('opcache.enable') ? opcache_get_status(false) : [];

        $dependencies = $this->getDependencies();

        $data = @[
            'PHP' => [
                'Version'             => PHP_VERSION,
                'Operating System'    => php_uname(),
                'Server API'          => PHP_SAPI,
                'Loaded php.ini'      => php_ini_loaded_file(),
                'Loaded Extensions'   => implode(', ', get_loaded_extensions()),
                'Stream Wrappers'     => implode(', ', stream_get_wrappers()),
                'Zend Engine Version' => zend_version()
            ],
            'HTTP Request Headers'  => HTTPRequest::headers()->toArray(),
            'HTTP Response Headers' => HTTPResponse::headers(),
            'Server'                => [
                'IP Address'   => $_SERVER['SERVER_ADDR'],
                'Port'         => $_SERVER['SERVER_PORT'],
                'Name'         => $_SERVER['SERVER_NAME'],
                'Software'     => $_SERVER['SERVER_SOFTWARE'],
                'Protocol'     => $_SERVER['SERVER_PROTOCOL'],
                'HTTPS'        => HTTPRequest::isHTTPS() ? 'on' : 'off',
                'Request Time' => gmdate('D, d M Y H:i:s T', $_SERVER['REQUEST_TIME'])
            ],
            'Client' => [
                'IP Address' => HTTPRequest::ip(),
                'Port'       => $_SERVER['REMOTE_PORT']
            ],
            'Session' => [
                'Session Cookie Lifetime' => ini_get('session.cookie_lifetime'),
                'Session Strict Mode'     => ini_get('session.use_strict_mode') ? 'true' : 'false'
            ],
            'Uploads' => [
                'File Uploads'         => ini_get('file_uploads') ? 'true' : 'false',
                'POST Max Size'        => ini_get('post_max_size'),
                'Maximum File Size'    => ini_get('upload_max_filesize'),
                'Maximum File Uploads' => ini_get('max_file_uploads')
            ],
            'Script' => [
                'Max Execution Time' => ini_get('max_execution_time'),
                'Max Input Time'     => ini_get('max_input_time'),
                'Memory Limit'       => ini_get('memory_limit'),
                'Default MIME-Type'  => ini_get('default_mimetype'),
                'Default Charset'    => ini_get('default_charset')
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
                'Max Wasted Percentage'     => ini_get('opcache.max_wasted_percentage')
            ],
            'System' => [
                'Directory Separator' => DS,
                'EOL Symbol'          => addcslashes(PHP_EOL, "\r\n"),
                'Max Path Length'     => FileSystem::MAX_PATH_LENGTH,
                'File Creation Mask'  => sprintf('0%03o', umask())
            ],
            'Formwork' => [
                'Formwork Version' => Formwork::VERSION,
                'Root Path'        => ROOT_PATH,
                'Formwork Path'    => FORMWORK_PATH,
                'Config Path'      => CONFIG_PATH
            ],
            'Dependencies' => [
                'Parsedown Version'       => $dependencies['erusev/parsedown']['version'],
                'Parsedown Extra Version' => $dependencies['erusev/parsedown-extra']['version'],
                'Symfony Yaml Version'    => $dependencies['symfony/yaml']['version']
            ]
        ];

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);

        return new Response($this->view('options.info', [
            'title'   => $this->admin()->translate('admin.options.options'),
            'tabs'    => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'info'
            ], true),
            'info' => $data
        ], true));
    }

    /**
     * Update options of a given type with given data
     *
     * @param string $type     Options type ('system' or 'site')
     * @param Fields $fields   Fields object
     * @param array  $options  Current options
     * @param array  $defaults Default values
     *
     * @return bool Whether new values were applied or not
     */
    protected function updateOptions(string $type, Fields $fields, array $options, array $defaults): bool
    {
        // Flatten fields
        $fields = $fields->toArray(true);

        $old = $options;
        $options = [];

        // Update options with new values
        foreach ($fields as $field) {
            if (in_array($field->type(), Validator::IGNORED_FIELDS, true)) {
                continue;
            }
            if ($field->isRequired() && $field->isEmpty()) {
                continue;
            }
            if (!Arr::has($defaults, $field->name()) || Arr::get($defaults, $field->name()) !== $field->value()) {
                Arr::set($options, $field->name(), $field->value());
            }
        }

        // Update config file if options differ
        if ($options !== $old) {
            YAML::encodeToFile($options, CONFIG_PATH . $type . '.yml');
            return true;
        }

        // Return false if options do not differ
        return false;
    }

    /**
     * Load dependencies data from composer.lock
     */
    protected function getDependencies(): array
    {
        $dependencies = [];
        if (FileSystem::exists(ROOT_PATH . 'composer.lock')) {
            $composerLock = JSON::parseFile(ROOT_PATH . 'composer.lock');
            foreach ($composerLock['packages'] as $package) {
                $dependencies[$package['name']] = $package;
            }
        }
        return $dependencies;
    }
}
