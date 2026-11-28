<?php

namespace Formwork\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Data\Collection;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Log\Logger;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Users\Attributes\Permission;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use JsonException;

/**
 * @phpstan-type LogHandler array{type?: string, path: string, formatter?: string, channels?: list<string>}
 * @phpstan-type LogEntry array{timestamp: int, channel: string, level: string, message: string, context: string}
 */
final class ToolsController extends AbstractController
{
    /**
     * Cached composer.json data
     *
     * @var array<string, mixed>
     */
    private array $composerJson;

    /**
     * Tools@index action
     */
    #[Permission('panel.tools.backups')]
    public function index(): RedirectResponse
    {
        return $this->redirect($this->generateRoute('panel.tools.backups'));
    }

    /**
     * Tools@backups action
     */
    #[Permission('panel.tools.backups')]
    public function backups(Backupper $backupper): Response
    {
        $backups = Arr::map($backupper->getBackups(), fn(string $path, int $timestamp): array => [
            'name'        => basename($path),
            'encodedName' => rawurlencode(base64_encode(basename($path))),
            'timestamp'   => $timestamp,
            'size'        => FileSystem::formatSize(FileSystem::size($path)),
        ]);

        return new Response($this->view('@panel.tools.backups', [
            'title'   => $this->translate('panel.tools.backups'),
            'backups' => Collection::from($backups),
        ]));
    }

    /**
     * Tools@updates action
     */
    #[Permission('panel.tools.updates')]
    public function updates(): Response
    {
        return new Response($this->view('@panel.tools.updates', [
            'title'          => $this->translate('panel.tools.updates'),
            'currentVersion' => $this->app::VERSION,
        ]));
    }

    /**
     * Tools@logs action
     */
    #[Permission('panel.tools.logs')]
    public function logs(RouteParams $routeParams): Response|RedirectResponse
    {
        $limits = [25, 50, 100, 200];
        $defaultLimit = 50;

        $channels = $this->getLogChannels();

        $query = $this->request->query();

        // Allow selecting the channel from a plain GET form
        if ($query->has('channel')) {
            $selected = (string) $query->get('channel');
            $route = $this->generateRoute('panel.tools.logs', ['channel' => in_array($selected, $channels, true) ? $selected : Logger::DEFAULT_CHANNEL]);
            /** @var array<string> $parameters */
            $parameters = Arr::filter(['limit' => $query->get('limit'), 'level' => $query->get('level')], fn($value) => is_string($value) && !Constraint::isEmpty($value));

            // The default limit is omitted from the query
            if ((int) ($parameters['limit'] ?? 0) === $defaultLimit) {
                unset($parameters['limit']);
            }

            return $this->redirect(Uri::make(['query' => $parameters], $route));
        }

        $channel = $routeParams->get('channel', Logger::DEFAULT_CHANNEL);

        if (!in_array($channel, $channels, true)) {
            return $this->redirect($this->generateRoute('panel.tools.logs', ['channel' => Logger::DEFAULT_CHANNEL]));
        }

        $limit = (int) $query->get('limit', $defaultLimit);

        if (!in_array($limit, $limits, true)) {
            $limit = $defaultLimit;
        }

        $levels = array_keys(Logger::LOG_LEVELS);

        $level = $query->get('level');

        if (!is_string($level) || !in_array($level, $levels, true)) {
            $level = null;
        }

        return new Response($this->view('@panel.tools.logs', [
            'title'    => $this->translate('panel.tools.logs'),
            'channels' => $channels,
            'channel'  => $channel,
            'limits'   => $limits,
            'limit'    => $limit,
            'levels'   => $levels,
            'level'    => $level,
            'entries'  => $this->readLogEntries($channel, $limit, $level),
        ]));
    }

    /**
     * Tools@clearLogs action
     */
    #[Permission('panel.tools.logs.clear')]
    public function clearLogs(RouteParams $routeParams): RedirectResponse
    {
        $channel = $routeParams->get('channel');

        if (in_array($channel, $this->getLogChannels(), true)) {
            $this->clearLogEntries($channel);
            $this->panel->notify($this->translate('panel.tools.logs.cleared'), 'success');
        } else {
            $channel = Logger::DEFAULT_CHANNEL;
        }

        return $this->redirect($this->generateRoute('panel.tools.logs', ['channel' => $channel]));
    }

    /**
     * Tools@info action
     */
    #[Permission('panel.tools.info')]
    public function info(): Response
    {
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
            'Disk Usage'       => FileSystem::formatSize(FileSystem::directorySize($this->app->rootPath())),
        ];

        $warnings = [];

        if ($this->config->getBool('system.debug.enabled')) {
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
                'Root Path'     => $this->app->rootPath(),
                'Formwork Path' => $this->app->systemPath(),
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
            'Environment Variables' => Arr::reject([...getenv(), ...$this->request->server()->toArray()], fn($value, $key) => str_starts_with($key, 'HTTP_')),
            'Server'                => [
                'Apache Modules' => implode(', ', function_exists('apache_get_modules') ? apache_get_modules() : []),
                'HTTPS'          => $this->request->isSecure() ? 'on' : 'off',
                'Request Time'   => gmdate('D, d M Y H:i:s T', (int) $this->request->server()->get('REQUEST_TIME')),
            ],
            'Client' => [
                'IP Address' => $this->request->ip(),
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
                'AVIF Support'       => $gdInfo['AVIF Support'] ?? '' ? 'true' : 'false',
            ],
        ];

        ksort($data['HTTP Request Headers']);
        ksort($data['HTTP Response Headers']);
        ksort($data['Environment Variables']);

        return new Response($this->view('@panel.tools.info', [
            'title'    => $this->translate('panel.tools.info'),
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
        if (FileSystem::exists($this->app->rootPath() . '/composer.lock')) {
            $composerLock = Json::parseFile($this->app->rootPath() . '/composer.lock');
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
                if ($package !== 'php' && !str_starts_with($package, 'ext-')) {
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
                if (str_starts_with($package, 'ext-')) {
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
        if (FileSystem::exists($this->app->rootPath() . '/composer.json')) {
            return $this->composerJson = Json::parseFile($this->app->rootPath() . '/composer.json');
        }
        return $this->composerJson = [];
    }

    /**
     * Get the file log handlers, optionally only those handling a channel
     *
     * @return array<LogHandler>
     */
    private function getLogHandlers(?string $channel = null): array
    {
        return Arr::filter(
            $this->config->getArray('system.logs.handlers', []),
            fn(array $handler) => ($handler['type'] ?? null) === 'file'
                && FileSystem::isFile($handler['path'], assertExists: false)
                && ($channel === null || ($handler['channels'] ?? []) === [] || in_array($channel, $handler['channels'], true))
        );
    }

    /**
     * Get the channels that can be read from the file log handlers
     *
     * @return list<string>
     */
    private function getLogChannels(): array
    {
        $channels = [Logger::DEFAULT_CHANNEL];

        foreach ($this->getLogHandlers() as $handler) {
            foreach ($handler['channels'] ?? [] as $channel) {
                if (!in_array($channel, $channels, true)) {
                    $channels[] = $channel;
                }
            }
        }

        return $channels;
    }

    /**
     * Read the latest entries of a log channel, most recent first
     *
     * @return list<LogEntry>
     */
    private function readLogEntries(string $channel, int $limit, ?string $level = null): array
    {
        $entries = [];

        foreach ($this->getLogHandlers($channel) as $handler) {
            foreach (array_reverse($this->readLogLines($handler['path'])) as $line) {
                $entry = $this->parseLogLine($line, $handler['formatter'] ?? 'json');

                if ($entry !== null && $entry['channel'] === $channel && ($level === null || $entry['level'] === $level)) {
                    $entries[] = $entry;
                }
            }
        }

        $entries = Arr::sort($entries, SORT_DESC, sortBy: Arr::extract($entries, 'timestamp'), preserveKeys: false);

        return array_slice($entries, 0, $limit);
    }

    /**
     * Remove the entries of a log channel from the log files, leaving those of other channels untouched
     *
     * Files left without entries are deleted
     */
    private function clearLogEntries(string $channel): void
    {
        foreach ($this->getLogHandlers($channel) as $handler) {
            $kept = Arr::filter($this->readLogLines($handler['path']), fn(string $line) => ($entry = $this->parseLogLine($line, $handler['formatter'] ?? 'json')) !== null && $entry['channel'] !== $channel);

            if ($kept === []) {
                // The file is created again by the handler when something is logged
                FileSystem::delete($handler['path']);
            } else {
                FileSystem::write($handler['path'], implode(PHP_EOL, $kept) . PHP_EOL);
            }
        }
    }

    /**
     * Get the non-empty lines of a log file
     *
     * @return list<string>
     */
    private function readLogLines(string $path): array
    {
        return preg_split('/\R/', FileSystem::read($path), flags: PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Parse a log line, returning null if it is invalid
     *
     * @return LogEntry|null
     */
    private function parseLogLine(string $line, string $formatter): ?array
    {
        if ($formatter === 'text') {
            if (preg_match('/^\[(.+?)\] (?:(.+?)\.)?([A-Za-z]+): (.*)$/', $line, $matches) !== 1) {
                return null;
            }

            [, $datetime, $channel, $level, $message] = $matches;
            $context = '';
        } else {
            try {
                $data = Json::parse($line);
            } catch (JsonException) {
                return null;
            }

            if (!is_string($data['datetime'] ?? null) || !is_string($data['level'] ?? null) || !is_string($data['message'] ?? null)) {
                return null;
            }

            ['datetime' => $datetime, 'level' => $level, 'message' => $message] = $data;
            $channel = is_string($data['channel'] ?? null) ? $data['channel'] : '';
            $context = Constraint::isEmpty($data['context'] ?? null) ? '' : Json::encode($data['context']);
        }

        return [
            'timestamp' => (int) strtotime($datetime),
            'channel'   => $channel === '' ? Logger::DEFAULT_CHANNEL : $channel,
            'level'     => strtolower($level),
            'message'   => $message,
            'context'   => $context,
        ];
    }
}
