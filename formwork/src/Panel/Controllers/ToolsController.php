<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Data\Collection;
use Formwork\Http\Response;
use Formwork\Parsers\Json;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

final class ToolsController extends AbstractController
{
    /**
     * All options tabs
     *
     * @var list<string>
     */
    private array $tabs = ['backups', 'updates', 'info'];

    /**
     * Cached composer.json data
     *
     * @var array<string, mixed>
     */
    private array $composerJson;

    /**
     * Tools@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('panel.tools.backups')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return $this->redirect($this->generateRoute('panel.tools.backups'));
    }

    /**
     * Tools@backups action
     */
    public function backups(): Response
    {
        if (!$this->hasPermission('panel.tools.backups')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $backupper = new Backupper([...$this->config->get('system.backup'), 'hostname' => $this->request->host()]);

        $backups = Arr::map($backupper->getBackups(), fn(string $path, int $timestamp): array => [
            'name'        => basename($path),
            'encodedName' => rawurlencode(base64_encode(basename($path))),
            'timestamp'   => $timestamp,
            'size'        => FileSystem::formatSize(FileSystem::size($path)),
        ]);

        return new Response($this->view('tools.backups', [
            'title' => $this->translate('panel.tools.backups'),
            'tabs'  => $this->view('tools.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'backups',
            ]),
            'backups' => Collection::from($backups),
        ]));
    }

    /**
     * Tools@updates action
     */
    public function updates(): Response
    {
        if (!$this->hasPermission('panel.tools.updates')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return new Response($this->view('tools.updates', [
            'title' => $this->translate('panel.tools.updates'),
            'tabs'  => $this->view('tools.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'updates',
            ]),
            'currentVersion' => $this->app::VERSION,
        ]));
    }

    /**
     * Tools@info action
     */
    public function info(): Response
    {
        if (!$this->hasPermission('panel.tools.info')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $opcacheStatus = extension_loaded('zend opcache') ? (opcache_get_status(false) ?: []) : [];

        $gdInfo = extension_loaded('gd') ? gd_info() : [];

        $dependenciesData = $this->getDependencies();
        $dependencies = [];

        foreach ($this->getRequiredDependencies() as $package) {
            if (isset($dependenciesData[$package])) {
                $dependencies[$package] = $dependenciesData[$package]['version'];
            }
        }

        $formwork = [
            'Formwork Version' => $this->app::VERSION,
            'PHP Version'      => PHP_VERSION,
            'Disk Usage'       => FileSystem::formatSize(FileSystem::directorySize(ROOT_PATH)),
        ];

        $warnings = [];

        if ($this->config->get('system.debug.enabled')) {
            $warnings[] = 'Debug mode enabled, remember to turn it off in production';
        }

        $missingExtensions = Arr::reject($this->getRequiredExtensions(), fn($extension) => extension_loaded($extension));

        if (($missingCount = count($missingExtensions)) > 0) {
            $warnings[] = sprintf(
                $missingCount > 1 ? 'Required PHP extensions %s not loaded, check PHP configuration' : 'Required PHP extension %s not loaded, check PHP configuration',
                implode(', ', Arr::map($missingExtensions, fn($extension) => Str::wrap($extension, '`')))
            );
        }

        if (!$this->request->isSecure()) {
            $warnings[] = 'Insecure connection detected, using HTTPS is recommended';
        }

        $data = @[
            'Dependencies' => $dependencies,
            'System'       => [
                'Directory Separator' => DIRECTORY_SEPARATOR,
                'EOL Symbol'          => addcslashes(PHP_EOL, "\r\n"),
                'Max Path Length'     => FileSystem::MAX_PATH_LENGTH,
                'File Creation Mask'  => sprintf('0%03o', umask()),
            ],
            'Paths' => [
                'Root Path'     => ROOT_PATH,
                'Formwork Path' => SYSTEM_PATH,
            ],
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
            'Output Buffering' => [
                'Output Buffering' => ini_get('output_buffering') ? 'true' : 'false',
                'Implicit Flush'   => ini_get('implicit_flush') ? 'true' : 'false',
                'Chunk Size'       => ini_get('output_buffering') !== '1' ? ini_get('output_buffering') : 'unlimited',
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
        ];

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);

        return new Response($this->view('tools.info', [
            'title' => $this->translate('panel.tools.info'),
            'tabs'  => $this->view('tools.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'info',
            ]),
            'formwork' => $formwork,
            'warnings' => $warnings,
            'info'     => $data,
        ]));
    }

    /**
     * Get headers from current request
     *
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            [$key, $value] = explode(':', $header, 2);
            $headers[$key] = trim($value);
        }
        return $headers;
    }

    /**
     * Load dependencies data from composer.lock
     *
     * @return array<string, mixed>
     */
    private function getDependencies(): array
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

    /**
     * Get required dependencies from composer.json
     *
     * @return list<string>
     */
    private function getRequiredDependencies(): array
    {
        $required = [];
        if (($composer = $this->getComposerJson()) !== []) {
            foreach ($composer['require'] as $package => $version) {
                if ($package !== 'php' && !Str::startsWith($package, 'ext-')) {
                    $required[] = $package;
                }
            }
        }
        return $required;
    }

    /**
     * Get required PHP extensions from composer.json
     *
     * @return list<string>
     */
    private function getRequiredExtensions(): array
    {
        $extensions = [];
        if (($composer = $this->getComposerJson()) !== []) {
            foreach ($composer['require'] as $package => $version) {
                if (Str::startsWith($package, 'ext-')) {
                    $extensions[] = Str::after($package, 'ext-');
                }
            }
        }
        return $extensions;
    }

    /**
     * Get composer.json data
     *
     * @return array<string, mixed>
     */
    private function getComposerJson(): array
    {
        if (isset($this->composerJson)) {
            return $this->composerJson;
        }
        if (FileSystem::exists(ROOT_PATH . '/composer.json')) {
            return $this->composerJson = Json::parseFile(ROOT_PATH . '/composer.json');
        }
        return $this->composerJson = [];
    }
}
