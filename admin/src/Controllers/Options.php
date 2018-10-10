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
    public function index()
    {
        $this->redirect('/options/system/', 302, true);
    }

    public function system()
    {
        $fields = new Fields(YAML::parseFile(SCHEMES_PATH . 'system.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postDataFromRaw());
            $options = Formwork::instance()->options();
            $defaults = Formwork::instance()->defaults();
            $differ = $this->updateOptions('system', $fields->validate($data), $options, $defaults);
            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/system/', 302, true);
        }

        $fields->validate(new DataGetter(Formwork::instance()->options()));

        $this->modal('changes');

        $this->view('admin', array(
            'title' => $this->label('options.options'),
            'content' => $this->view('options.system', array(
                'tabs' => $this->view('options.tabs', array(
                    'tab' => 'system'
                ), false),
                'fields' => $this->fields($fields, false)
            ), false)
        ));
    }

    public function site()
    {
        $fields = new Fields(YAML::parseFile(SCHEMES_PATH . 'site.yml'));

        if (HTTPRequest::method() === 'POST') {
            $data = new DataGetter(HTTPRequest::postDataFromRaw());
            $options = Formwork::instance()->site()->data();
            $differ = $this->updateOptions('site', $fields->validate($data), $options, array());

            // Touch content folder to invalidate cache
            if ($differ) {
                FileSystem::touch(Formwork::instance()->option('content.path'));
            }

            $this->notify($this->label('options.updated'), 'success');
            $this->redirect('/options/site/', 302, true);
        }

        $fields->validate(new DataGetter(Formwork::instance()->site()->data()));

        $this->modal('changes');

        $this->view('admin', array(
            'title' => $this->label('options.options'),
            'content' => $this->view('options.site', array(
                'tabs' => $this->view('options.tabs', array(
                    'tab' => 'site'
                ), false),
                'fields' => $this->fields($fields, false)
            ), false)
        ));
    }

    public function updates()
    {
        $this->view('admin', array(
            'title' => $this->label('options.updates'),
            'content' => $this->view('options.updates', array(
                'tabs' => $this->view('options.tabs', array(
                    'tab' => 'updates'
                ), false),
                'currentVersion' => Formwork::VERSION
            ), false)
        ));
    }

    public function info()
    {
        $dependencies = $this->getDependencies();

        $data = @array(
            'PHP' => array(
                'Version' => PHP_VERSION,
                'Operating System' => php_uname(),
                'Server API' => PHP_SAPI,
                'Loaded php.ini' => php_ini_loaded_file(),
                'Loaded Extensions' => implode(', ', get_loaded_extensions()),
                'Stream Wrappers' => implode(', ', stream_get_wrappers()),
                'Zend Engine Version' => zend_version()
            ),
            'HTTP Request Headers' => HTTPRequest::headers(),
            'HTTP Response Headers' => HTTPResponse::headers(),
            'Server' => array(
                'IP Address' => $_SERVER['SERVER_ADDR'],
                'Port' => $_SERVER['SERVER_PORT'],
                'Name' => $_SERVER['SERVER_NAME'],
                'Software' => $_SERVER['SERVER_SOFTWARE'],
                'Protocol' => $_SERVER['SERVER_PROTOCOL'],
                'HTTPS' => HTTPRequest::isHTTPS() ? 'on' : 'off',
                'Request Time' => gmdate('D, d M Y H:i:s T', $_SERVER['REQUEST_TIME'])
            ),
            'Client' => array(
                'IP Address' => HTTPRequest::ip(),
                'Port' => $_SERVER['REMOTE_PORT']
            ),
            'Session' => array(
                'Session Cookie Lifetime' => ini_get('session.cookie_lifetime'),
                'Session Strict Mode' => ini_get('session.use_strict_mode')
            ),
            'Uploads' => array(
                'File Uploads' => ini_get('file_uploads'),
                'POST Max Size' => ini_get('post_max_size'),
                'Maximum File Size' => ini_get('upload_max_filesize'),
                'Maximum File Uploads' => ini_get('max_file_uploads')
            ),
            'Script' => array(
                'Max Execution Time' => ini_get('max_execution_time'),
                'Max Input Time' => ini_get('max_input_time'),
                'Memory Limit' => ini_get('memory_limit'),
                'Default MIME-Type' => ini_get('default_mimetype'),
                'Default Charset' => ini_get('default_charset')
            ),
            'Formwork' => array(
                'Formwork Version' => Formwork::VERSION,
                'Directory Separator' => DS,
                'EOL Symbol' => addcslashes(PHP_EOL, "\r\n"),
                'Root Path' => ROOT_PATH,
                'Formwork Path' => FORMWORK_PATH,
                'Config Path' => CONFIG_PATH,
                'Parsedown Version' => $dependencies['erusev/parsedown']['version'],
                'Parsedown Extra Version' => $dependencies['erusev/parsedown-extra']['version'],
                'Spyc Version' => $dependencies['mustangostang/spyc']['version']
            )
        );

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);

        $this->view('admin', array(
            'title' => $this->label('options.options'),
            'content' => $this->view('options.info', array(
                'tabs' => $this->view('options.tabs', array(
                    'tab' => 'info'
                ), false),
                'info' => $data
            ), false)
        ));
    }

    protected function updateOptions($type, Fields $fields, $options, $defaults)
    {
        // Fields to ignore
        $ignore = array('column', 'header', 'row', 'rows');

        // Flatten fields
        $fields = $fields->toArray(true);

        $old = $options;

        // Update options with new values
        foreach ($fields as $field) {
            if (in_array($field->type(), $ignore)) {
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
