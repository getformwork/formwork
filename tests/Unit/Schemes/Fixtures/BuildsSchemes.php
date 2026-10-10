<?php

namespace Formwork\Tests\Unit\Schemes\Fixtures;

use Formwork\Config\Config;
use Formwork\Fields\FieldFactory;
use Formwork\Schemes\Scheme;
use Formwork\Schemes\SchemeFactory;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;

/**
 * Builds a real Schemes/FieldFactory/Translations stack over files in the temporary directory
 *
 * @mixin \Formwork\Tests\TestCase
 */
trait BuildsSchemes
{
    protected string $schemesPath;

    protected string $fieldsPath;

    protected Container $container;

    protected Translations $translations;

    protected Schemes $schemes;

    protected FieldFactory $fieldFactory;

    protected function setUpSchemes(string $currentLanguage = 'en'): void
    {
        $this->setUpTempDirectory();

        $base = FileSystem::joinPaths(TESTS_TMP_PATH, 'schemes-sandbox');
        $this->schemesPath = $base . '/schemes';
        $this->fieldsPath = $base . '/fields';
        $translationsPath = $base . '/translations';

        // A crashed process (e.g. a fatal error in a child test) may have left the sandbox behind
        if (FileSystem::isDirectory($base, assertExists: false)) {
            FileSystem::deleteDirectory($base, recursive: true);
        }

        FileSystem::createDirectory($this->schemesPath, recursive: true);
        FileSystem::createDirectory($this->fieldsPath, recursive: true);
        FileSystem::createDirectory($translationsPath, recursive: true);

        FileSystem::write($translationsPath . '/en.yaml', "greeting: Hello\nsection.main: Main section\nonly.english: English only\n");
        FileSystem::write($translationsPath . '/it.yaml', "greeting: Ciao\nsection.main: Sezione principale\n");

        FileSystem::write($this->fieldsPath . '/text.php', "<?php\nreturn function () {\n    return ['default' => '', 'methods' => ['upper' => fn(\$field) => strtoupper((string) \$field->value())]];\n};\n");
        FileSystem::write($this->fieldsPath . '/title.php', "<?php\nreturn function () {\n    return ['extend' => 'text', 'default' => 'Untitled', 'methods' => ['shout' => fn(\$field) => \$field->value() . '!']];\n};\n");
        FileSystem::write($this->fieldsPath . '/headline.php', "<?php\nreturn function () {\n    return ['extend' => 'title', 'methods' => ['upper' => fn(\$field) => 'HEADLINE']];\n};\n");
        FileSystem::write($this->fieldsPath . '/loop-a.php', "<?php\nreturn function () {\n    return ['extend' => 'loop-b'];\n};\n");
        FileSystem::write($this->fieldsPath . '/loop-b.php', "<?php\nreturn function () {\n    return ['extend' => 'loop-a'];\n};\n");

        $config = new Config(['system' => [
            'fields'       => ['path' => $this->fieldsPath],
            'translations' => ['fallback' => 'en'],
        ]], resolved: true);

        $this->translations = new Translations($config);
        $this->translations->loadFromPath($translationsPath);
        $this->translations->setCurrent($currentLanguage);

        $this->container = new Container();
        $this->container->define(Container::class, $this->container);
        $this->container->define(Config::class, $config);
        $this->container->define(Translations::class, $this->translations);
        $this->container->define(SchemeFactory::class);
        $this->container->define(FieldFactory::class);
        $this->container->define(Schemes::class);

        $this->schemes = $this->container->get(Schemes::class);
        $this->fieldFactory = $this->container->get(FieldFactory::class);
    }

    protected function tearDownSchemes(): void
    {
        $this->tearDownTempDirectory();
    }

    protected function writeScheme(string $id, string $yaml): void
    {
        FileSystem::write($this->schemesPath . '/' . $id . '.yaml', $yaml);
        $this->schemes->load($id, $this->schemesPath . '/' . $id . '.yaml');
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeScheme(string $id, array $data): Scheme
    {
        return new Scheme($id, $data, $this->translations, $this->schemes, $this->fieldFactory);
    }
}
