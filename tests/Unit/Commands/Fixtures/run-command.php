<?php

/**
 * Runs a command in a separate process, to test its termination with `exit`
 *
 * Usage: php run-command.php <command class> [<scenario>] -- [<argument>...]
 *
 * The scenario selects the (offline) release served to the updater, if any. The environment variable
 * `FORMWORK_TEST_BACKUPS` is the number of dummy backups to create before running the command, while
 * `FORMWORK_TEST_BROKEN_BACKUPS` makes the backup path unusable by replacing it with a file
 */

use Formwork\Cms\App;
use Formwork\Services\Container;
use Formwork\Tests\Unit\Commands\Fixtures\OfflineClient;
use Formwork\Updater\Updater;

require dirname(__DIR__, 3) . '/bootstrap.php';

$separator = array_search('--', $argv, true);
[$class, $scenario] = [$argv[1], $argv[2] ?? 'none'];
$arguments = array_slice($argv, $separator + 1);

if ($scenario !== 'none') {
    OfflineClient::$scenario = $scenario;

    $app = App::instance();
    $app->load();

    $app->getService(Container::class)->define(Updater::class, OfflineClient::updater($app->rootPath()));
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
