<?php

/**
 * Runs a command in a separate process (commands terminate the script with `exit`)
 *
 * Usage: php run-command.php <command class> [<scenario>] -- [<argument>...]
 *
 * The scenario selects the (offline) release served to the updater, if any. The environment variable
 * `FORMWORK_TEST_BACKUPS` is the number of dummy backups to create before running the command, while
 * `FORMWORK_TEST_BROKEN_BACKUPS` makes the backup path unusable by replacing it with a file
 */

use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Parsers\Json;
use Formwork\Services\Container;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;

require dirname(__DIR__, 3) . '/bootstrap.php';

$separator = array_search('--', $argv, true);
[$class, $scenario] = [$argv[1], $argv[2] ?? 'none'];
$arguments = array_slice($argv, $separator + 1);

final class OfflineClient extends Client
{
    public function __construct(private string $scenario) {}

    public function fetch(string $uri, array $options = []): Response
    {
        if ($this->scenario === 'unreachable') {
            throw new RuntimeException('Network is unreachable');
        }

        return new Response(Json::encode([
            'name'         => 'Release',
            'tag_name'     => $this->scenario === 'uptodate' ? App::VERSION : sprintf('%d.%d.0', SemVer::fromString(App::VERSION)->major(), SemVer::fromString(App::VERSION)->minor() + 1),
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
        if ($this->scenario === 'install') {
            $zip = new ZipArchive();
            $zip->open($file, ZipArchive::CREATE);
            $zip->addFromString('updated.txt', 'updated');
            $zip->close();
        }
    }
}

if ($scenario !== 'none') {
    $app = App::instance();
    $app->load();

    $root = $app->rootPath();

    $app->getService(Container::class)
        ->define(Updater::class, new Updater([
            'registryFile'        => $root . '/updates.json',
            'tempFile'            => $root . '/update.zip',
            'time'                => 900,
            'force'               => false,
            'preferDistAssets'    => true,
            'lockTimeout'         => 300,
            'cleanupAfterInstall' => false,
            'ignore'              => ['site/*'],
        ], $root, new OfflineClient($scenario)));
}

if (($backups = (int) getenv('FORMWORK_TEST_BACKUPS')) > 0) {
    $path = App::instance()->rootPath() . '/backup';
    mkdir($path, recursive: true);
    for ($i = 1; $i <= $backups; ++$i) {
        file_put_contents("{$path}/seeded-{$i}.zip", str_repeat('x', 1024 * $i));
        touch("{$path}/seeded-{$i}.zip", time() - 3600 * (10 - $i));
    }
}

if (getenv('FORMWORK_TEST_BROKEN_BACKUPS') === '1') {
    file_put_contents(App::instance()->rootPath() . '/backup', 'not a directory');
}

(new $class())(['bin/formwork test', ...$arguments]);
