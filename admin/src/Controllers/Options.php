<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Fields\Fields;
use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;

class Options extends AbstractController
{
    /**
     * All options tabs
     *
     * @var array
     */
    protected $tabs = ['system', 'site', 'updates', 'info'];

    /**
     * Options@index action
     */
    public function index(): void
    {
        $this->ensurePermission('options.system');
        $this->redirect('/options/system/');
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(): void
    {
        $this->ensurePermission('options.system');

        $fields = new Fields(YAML::parseFile(Admin::SCHEMES_PATH . 'system.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postData());
            $options = Formwork::instance()->options();
            $defaults = Formwork::instance()->defaults();
            $differ = $this->updateOptions('system', $fields->validate($data), $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch($this->option('content.path'));
            }

            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/system/');
        }

        $fields->validate(new DataGetter(Formwork::instance()->options()));

        $this->modal('changes');

        $this->view('admin', [
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.system', [
                'tabs' => $this->view('options.tabs', [
                    'tabs'    => $this->tabs,
                    'current' => 'system'
                ], true),
                'fields' => $fields->render(true)
            ], true)
        ]);
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(): void
    {
        $this->ensurePermission('options.site');

        $fields = new Fields(YAML::parseFile(Admin::SCHEMES_PATH . 'site.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postData());
            $options = $this->site()->data();
            $defaults = Formwork::instance()->site()->defaults();
            $differ = $this->updateOptions('site', $fields->validate($data), $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch($this->option('content.path'));
            }

            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/site/');
        }

        $fields->validate(new DataGetter($this->site()->data()));

        $this->modal('changes');

        $this->view('admin', [
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.site', [
                'tabs' => $this->view('options.tabs', [
                    'tabs'    => $this->tabs,
                    'current' => 'site'
                ], true),
                'fields' => $fields->render(true)
            ], true)
        ]);
    }

    /**
     * Options@updates action
     */
    public function updates(): void
    {
        $this->ensurePermission('options.updates');

        $this->view('admin', [
            'title'   => $this->label('options.updates'),
            'content' => $this->view('options.updates', [
                'tabs' => $this->view('options.tabs', [
                    'tabs'    => $this->tabs,
                    'current' => 'updates'
                ], true),
                'currentVersion' => Formwork::VERSION
            ], true)
        ]);
    }

    /**
     * Options@info action
     */
    public function info(): void
    {
        $this->ensurePermission('options.info');

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
            'HTTP Request Headers'  => HTTPRequest::headers(),
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
            'System' => [
                'Directory Separator' => DS,
                'EOL Symbol'          => addcslashes(PHP_EOL, "\r\n")
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

        $this->view('admin', [
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.info', [
                'tabs' => $this->view('options.tabs', [
                    'tabs'    => $this->tabs,
                    'current' => 'info'
                ], true),
                'info' => $data
            ], true)
        ]);
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
        // Fields to ignore
        $ignore = ['column', 'header', 'row', 'rows'];

        // Flatten fields
        $fields = $fields->toArray(true);

        $old = $options;

        // Update options with new values
        foreach ($fields as $field) {
            if (in_array($field->type(), $ignore, true)) {
                continue;
            }
            if ($field->get('required') && $field->isEmpty()) {
                continue;
            }
            $options[$field->name()] = $field->value();
        }

        // Unset default values
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $defaults) && $defaults[$key] == $value) {
                unset($options[$key]);
            }
        }

        // Update config file if options differ
        if ($options !== $old) {
            FileSystem::write(CONFIG_PATH . $type . '.yml', YAML::encode($options));
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
            $composerLock = json_decode(FileSystem::read(ROOT_PATH . 'composer.lock'), true);
            foreach ($composerLock['packages'] as $package) {
                $dependencies[$package['name']] = $package;
            }
        }
        return $dependencies;
    }
}
