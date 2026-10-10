<?php

namespace Formwork\Tests\Unit\Pages\Fixtures;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Utils\FileSystem;

/**
 * Builds sites over copies of directory trees in the temporary path
 *
 * @mixin \Formwork\Tests\TestCase
 */
trait BuildsPageSites
{
    private int $siteCounter = 0;

    /**
     * Create a site whose content is a copy of the given fixture directory
     */
    protected function siteFromFixture(string $fixturePath = __DIR__ . '/site'): Site
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'site-' . ++$this->siteCounter);
        FileSystem::copyDirectory($fixturePath, $path);

        return $this->siteFromPath($path);
    }

    /**
     * Create a site over an existing content directory
     */
    protected function siteFromPath(string $path): Site
    {
        $app = App::instance();

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $app->config(),
            $app->getService(PageFactory::class),
            $app->getService(PageCollectionFactory::class),
        );
    }

    /**
     * Create a site from a map of relative paths to file contents
     *
     * @param array<string, string> $files
     */
    protected function siteFromFiles(array $files): Site
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'site-' . ++$this->siteCounter);
        FileSystem::createDirectory($path, recursive: true);

        foreach ($files as $relativePath => $content) {
            $file = $path . '/' . $relativePath;
            if (!FileSystem::isDirectory(dirname($file), assertExists: false)) {
                FileSystem::createDirectory(dirname($file), recursive: true);
            }
            FileSystem::write($file, $content);
        }

        return $this->siteFromPath($path);
    }

    protected function pageAt(Site $site, string $route): Page
    {
        return $site->findPage($route) ?? $this->fail(sprintf('Page %s was not found', $route));
    }
}
