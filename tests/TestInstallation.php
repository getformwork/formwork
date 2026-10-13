<?php

namespace Formwork\Tests;

use Formwork\Parsers\Yaml;
use Formwork\Utils\FileSystem;

final class TestInstallation
{
    /**
     * Directories of `site` copied from the real installation
     */
    private const array SKELETON = ['schemes', 'templates', 'users/roles'];

    /**
     * Root path of the real installation the tests are run from
     */
    public static function rootPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Formwork system path of the real installation the tests are run from
     */
    public static function systemPath(): string
    {
        return self::rootPath() . '/formwork';
    }

    /**
     * Create a temporary installation, removed when the process ends, and return its root path
     */
    public static function create(): string
    {
        $rootPath = self::rootPath();
        $root = sys_get_temp_dir() . '/formwork-tests-' . getmypid() . '-' . bin2hex(random_bytes(4));

        foreach (self::SKELETON as $directory) {
            FileSystem::copyDirectory($rootPath . '/site/' . $directory, $root . '/site/' . $directory);
        }

        foreach (['config/plugins', 'files', 'pages', 'plugins', 'sessions', 'users/accounts', 'users/images'] as $directory) {
            FileSystem::createDirectory($root . '/site/' . $directory, recursive: true);
        }

        self::createPages($root . '/site/pages');

        FileSystem::copy($rootPath . '/site/config/site.yaml', $root . '/site/config/site.yaml');

        // The panel is not part of the skeleton, the configuration points to the real one
        $system = Yaml::parseFile($rootPath . '/site/config/system.yaml');
        $system['panel']['path'] = $rootPath . '/panel';
        FileSystem::write($root . '/site/config/system.yaml', Yaml::encode($system));

        register_shutdown_function(static function () use ($root): void {
            if (FileSystem::isDirectory($root, assertExists: false)) {
                FileSystem::deleteDirectory($root, recursive: true);
            }
        });

        return $root;
    }

    /**
     * Create the small content tree the tests rely on
     */
    private static function createPages(string $path): void
    {
        $pages = [
            'index/page.md'                => ['title' => 'Home'],
            'about/page.md'                => ['title' => 'About'],
            'error/page.md'                => ['title' => 'Page Not Found', 'routable' => false, 'cacheable' => false, 'allowChildren' => false, 'responseStatus' => 404],
            'blog/blog.md'                 => ['title' => 'Blog', 'postsPerPage' => 2],
            'blog/20240101-first/post.md'  => ['title' => 'First', 'publishDate' => '2024-01-01 00:00:00', 'taxonomy' => ['tag' => ['ideas']]],
            'blog/20240102-second/post.md' => ['title' => 'Second', 'publishDate' => '2024-01-02 00:00:00', 'taxonomy' => ['tag' => ['ideas', 'news']]],
            'blog/20240103-third/post.md'  => ['title' => 'Third', 'publishDate' => '2024-01-03 00:00:00'],
        ];

        foreach ($pages as $file => $data) {
            FileSystem::createDirectory(dirname($path . '/' . $file), recursive: true);
            FileSystem::write($path . '/' . $file, "---\n" . Yaml::encode($data) . "---\n" . $data['title'] . " content\n");
        }
    }
}
