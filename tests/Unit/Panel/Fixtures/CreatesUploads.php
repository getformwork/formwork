<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Cms\App;
use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;
use ReflectionProperty;

/**
 * Creates files to upload and an uploader accepting only the given destinations
 */
trait CreatesUploads
{
    /**
     * Create an uploaded PNG image
     */
    protected function uploadedImage(string $fieldName = 'files', string $clientName = 'photo.png'): MovableUploadedFile
    {
        $image = imagecreatetruecolor(8, 8);

        $path = $this->uploadTempPath($clientName);
        imagepng($image, $path);

        return MovableUploadedFile::from($fieldName, $path, $clientName, 'image/png');
    }

    /**
     * Create an uploaded plain text file, which is not an allowed type
     */
    protected function uploadedText(string $fieldName = 'files', string $clientName = 'notes.txt'): MovableUploadedFile
    {
        $path = $this->uploadTempPath($clientName);
        FileSystem::write($path, 'plain text');

        return MovableUploadedFile::from($fieldName, $path, $clientName, 'text/plain');
    }

    /**
     * @param list<string> $destinations Destinations accepted by the uploader
     * @param ?string      $pagesPath    Path of the pages, to generate the URIs of the uploaded files
     */
    protected function uploader(array $destinations, ?string $pagesPath = null): FileUploader
    {
        $app = App::instance();

        $config = new Config(['system' => [
            'files' => [
                'allowedExtensions' => $app->config()->getArray('system.files.allowedExtensions'),
                'uploads'           => ['baseDestinations' => $destinations],
                'paths'             => ['site' => $destinations[0]],
            ],
            'uploads' => ['processImages' => false],
            'pages'   => ['path' => $pagesPath ?? $app->config()->getString('system.pages.path')],
            'images'  => $app->config()->getArray('system.images'),
            'users'   => ['paths' => ['images' => $destinations[0]]],
            'panel'   => ['paths' => ['assets' => $app->config()->getString('system.panel.paths.assets')]],
        ]], resolved: true);

        $fileUriGenerator = new FileUriGenerator($config, new Request([], [], [], [], []), $app->getService(UriGenerator::class));

        // The associations of the file types are the ones of the application
        $associations = (new ReflectionProperty(FileFactory::class, 'associations'))->getValue($app->getService(FileFactory::class));

        return new FileUploader($config, new FileFactory(new UriGeneratorContainer($app->getService(Container::class), $fileUriGenerator), $app->config(), $associations));
    }

    private function uploadTempPath(string $clientName): string
    {
        $directory = TESTS_TMP_PATH . '/uploads';

        if (!FileSystem::isDirectory($directory, assertExists: false)) {
            FileSystem::createDirectory($directory);
        }

        return $directory . '/' . bin2hex(random_bytes(4)) . '-' . $clientName;
    }
}
