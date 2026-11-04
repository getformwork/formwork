<?php

namespace Formwork\Tests\Unit\Commands\Fixtures;

use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Parsers\Json;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use RuntimeException;
use ZipArchive;

/**
 * Serves a release without any network access
 *
 * The scenario can be `uptodate` (the latest release is the current one), `outdated` (a newer release
 * whose archive cannot be downloaded), `install` (a newer release with a valid archive)
 * or `unreachable` (the release information cannot be retrieved)
 */
final class OfflineClient extends Client
{
    public static string $scenario = 'uptodate';

    public function __construct() {}

    /**
     * Create an updater installing in the given root, which uses this client
     */
    public static function updater(string $root): Updater
    {
        return new Updater([
            'registryFile'        => $root . '/updates.json',
            'tempFile'            => $root . '/update.zip',
            'time'                => 900,
            'force'               => true,
            'preferDistAssets'    => true,
            'lockTimeout'         => 300,
            'cleanupAfterInstall' => false,
            'ignore'              => ['site/*'],
        ], $root, new self());
    }

    /**
     * Version of the newer release
     */
    public static function newerVersion(): string
    {
        $semVer = SemVer::fromString(App::VERSION);

        return sprintf('%d.%d.0', $semVer->major(), $semVer->minor() + 1);
    }

    public function fetch(string $uri, array $options = []): Response
    {
        if (self::$scenario === 'unreachable') {
            throw new RuntimeException('Network is unreachable');
        }

        return new Response(Json::encode([
            'name'         => 'Release',
            'tag_name'     => self::$scenario === 'uptodate' ? App::VERSION : self::newerVersion(),
            'published_at' => '2024-05-01T10:00:00Z',
            'zipball_url'  => 'https://api.example.test/zipball',
            'assets'       => [],
        ]));
    }

    public function fetchHeaders(string $uri, array $options = []): ResponseHeaders
    {
        return new ResponseHeaders(['ETag' => '"etag"']);
    }

    public function download(string $uri, string $file, array $options = []): void
    {
        if (self::$scenario === 'install') {
            $zip = new ZipArchive();
            $zip->open($file, ZipArchive::CREATE);
            $zip->addFromString('updated.txt', 'updated');
            $zip->close();
        }
    }
}
