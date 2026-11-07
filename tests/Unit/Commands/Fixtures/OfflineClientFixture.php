<?php

namespace Formwork\Tests\Unit\Commands\Fixtures;

use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Parsers\Json;
use Formwork\Updater\SemVer;
use RuntimeException;
use ZipArchive;

/**
 * Serves an offline release to the updater, selected by the static `$scenario`
 */
final class OfflineClientFixture extends Client
{
    /**
     * Release served to the updater: `uptodate`, `outdated`, `install` or `unreachable`
     */
    public static string $scenario = 'uptodate';

    public function fetch(string $uri, array $options = []): Response
    {
        if (self::$scenario === 'unreachable') {
            throw new RuntimeException('Network is unreachable');
        }

        return new Response(Json::encode([
            'name'         => 'Release',
            'tag_name'     => self::$scenario === 'uptodate' ? App::VERSION : sprintf('%d.%d.0', SemVer::fromString(App::VERSION)->major(), SemVer::fromString(App::VERSION)->minor() + 1),
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
