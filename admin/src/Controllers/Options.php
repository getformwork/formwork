<?php

namespace Formwork\Admin\Controllers;

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
    protected $tabs = array('system', 'site', 'updates', 'info');

    /**
     * Options@index action
     */
    public function index()
    {
        $this->ensurePermission('options.system');
        $this->redirect('/options/system/');
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions()
    {
        $this->ensurePermission('options.system');

        $fields = new Fields(YAML::parseFile(SCHEMES_PATH . 'system.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postDataFromRaw());
            $options = Formwork::instance()->options();
            $defaults = Formwork::instance()->defaults();
            $differ = $this->updateOptions('system', $fields->validate($data), $options, $defaults);
            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/system/');
        }

        $fields->validate(new DataGetter(Formwork::instance()->options()));

        $this->modal('changes');

        $this->view('admin', array(
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.system', array(
                'tabs' => $this->view('options.tabs', array(
                    'tabs'    => $this->tabs,
                    'current' => 'system'
                ), false),
                'fields' => $this->fields($fields, false)
            ), false)
        ));
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions()
    {
        $this->ensurePermission('options.site');

        $fields = new Fields(YAML::parseFile(SCHEMES_PATH . 'site.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postDataFromRaw());
            $options = $this->site()->data();
            $differ = $this->updateOptions('site', $fields->validate($data), $options, array());

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch($this->option('content.path'));
            }

            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/site/');
        }

        $fields->validate(new DataGetter($this->site()->data()));

        $this->modal('changes');

        $this->view('admin', array(
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.site', array(
                'tabs' => $this->view('options.tabs', array(
                    'tabs'    => $this->tabs,
                    'current' => 'site'
                ), false),
                'fields' => $this->fields($fields, false)
            ), false)
        ));
    }

    /**
     * Options@updates action
     */
    public function updates()
    {
        $this->ensurePermission('options.updates');

        $this->view('admin', array(
            'title'   => $this->label('options.updates'),
            'content' => $this->view('options.updates', array(
                'tabs' => $this->view('options.tabs', array(
                    'tabs'    => $this->tabs,
                    'current' => 'updates'
                ), false),
                'currentVersion' => Formwork::VERSION
            ), false)
        ));
    }

    /**
     * Options@info action
     */
    public function info()
    {
        $this->ensurePermission('options.info');

        $dependencies = $this->getDependencies();

        $data = @array(
            'PHP' => array(
                'Version'             => PHP_VERSION,
                'Operating System'    => php_uname(),
                'Server API'          => PHP_SAPI,
                'Loaded php.ini'      => php_ini_loaded_file(),
                'Loaded Extensions'   => implode(', ', get_loaded_extensions()),
                'Stream Wrappers'     => implode(', ', stream_get_wrappers()),
                'Zend Engine Version' => zend_version()
            ),
            'HTTP Request Headers'  => HTTPRequest::headers(),
            'HTTP Response Headers' => HTTPResponse::headers(),
            'Server'                => array(
                'IP Address'   => $_SERVER['SERVER_ADDR'],
                'Port'         => $_SERVER['SERVER_PORT'],
                'Name'         => $_SERVER['SERVER_NAME'],
                'Software'     => $_SERVER['SERVER_SOFTWARE'],
                'Protocol'     => $_SERVER['SERVER_PROTOCOL'],
                'HTTPS'        => HTTPRequest::isHTTPS() ? 'on' : 'off',
                'Request Time' => gmdate('D, d M Y H:i:s T', $_SERVER['REQUEST_TIME'])
            ),
            'Client' => array(
                'IP Address' => HTTPRequest::ip(),
                'Port'       => $_SERVER['REMOTE_PORT']
            ),
            'Session' => array(
                'Session Cookie Lifetime' => ini_get('session.cookie_lifetime'),
                'Session Strict Mode'     => ini_get('session.use_strict_mode') ? 'true' : 'false'
            ),
            'Uploads' => array(
                'File Uploads'         => ini_get('file_uploads') ? 'true' : 'false',
                'POST Max Size'        => ini_get('post_max_size'),
                'Maximum File Size'    => ini_get('upload_max_filesize'),
                'Maximum File Uploads' => ini_get('max_file_uploads')
            ),
            'Script' => array(
                'Max Execution Time' => ini_get('max_execution_time'),
                'Max Input Time'     => ini_get('max_input_time'),
                'Memory Limit'       => ini_get('memory_limit'),
                'Default MIME-Type'  => ini_get('default_mimetype'),
                'Default Charset'    => ini_get('default_charset')
            ),
            'System' => array(
                'Directory Separator' => DS,
                'EOL Symbol'          => addcslashes(PHP_EOL, "\r\n")
            ),
            'Formwork' => array(
                'Formwork Version' => Formwork::VERSION,
                'Root Path'        => ROOT_PATH,
                'Formwork Path'    => FORMWORK_PATH,
                'Config Path'      => CONFIG_PATH
            ),
            'Dependencies' => array(
                'Parsedown Version'       => $dependencies['erusev/parsedown']['version'],
                'Parsedown Extra Version' => $dependencies['erusev/parsedown-extra']['version'],
                'Symfony Yaml Version'    => $dependencies['symfony/yaml']['version']
            )
        );

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);

        $this->view('admin', array(
            'title'   => $this->label('options.options'),
            'content' => $this->view('options.info', array(
                'tabs' => $this->view('options.tabs', array(
                    'tabs'    => $this->tabs,
                    'current' => 'info'
                ), false),
                'info' => $data
            ), false)
        ));
    }

    /**
     * Update options of a given type with given data
     *
     * @param string $type     Options type ('system' or 'site')
     * @param array  $options  Current options
     * @param array  $defaults Default values
     *
     * @return bool Whether new values were applied or not
     */
    protected function updateOptions($type, Fields $fields, $options, $defaults)
    {
        // Fields to ignore
        $ignore = array('column', 'header', 'row', 'rows');

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
     *
     * @return array
     */
    protected function getDependencies()
    {
        $dependencies = array();
        if (FileSystem::exists(ROOT_PATH . 'composer.lock')) {
            $composerLock = json_decode(FileSystem::read(ROOT_PATH . 'composer.lock'), true);
            foreach ($composerLock['packages'] as $package) {
                $dependencies[$package['name']] = $package;
            }
        }
        return $dependencies;
    }
}
