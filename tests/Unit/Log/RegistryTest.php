<?php

namespace Formwork\Tests\Unit\Log;

use Formwork\Log\Registry;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Registry::class)]
final class RegistryTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->file = FileSystem::joinPaths(TESTS_TMP_PATH, 'registry', 'data.json');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testNewRegistriesAreEmpty(): void
    {
        $registry = new Registry($this->file);

        $this->assertSame([], $registry->toArray());
        $this->assertFalse($registry->has('key'));
    }

    public function testReadingDoesNotCreateTheFile(): void
    {
        $registry = new Registry($this->file);

        $registry->has('key');
        $registry->toArray();
        unset($registry);

        $this->assertFileDoesNotExist($this->file);
    }

    public function testValuesCanBeSetAndRead(): void
    {
        $registry = new Registry($this->file);

        $registry->set('string', 'text');
        $registry->set('integer', 5);
        $registry->set('array', ['a' => [1, 2]]);

        $this->assertTrue($registry->has('string'));
        $this->assertSame('text', $registry->get('string'));
        $this->assertSame(5, $registry->get('integer'));
        $this->assertSame(['a' => [1, 2]], $registry->get('array'));
        $this->assertSame(['string' => 'text', 'integer' => 5, 'array' => ['a' => [1, 2]]], $registry->toArray());
    }

    public function testUndefinedKeysAreReported(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Undefined key "missing"');
        (new Registry($this->file))->get('missing');
    }

    public function testSettingAKeyAgainReplacesTheValue(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', 'old');
        $registry->set('key', 'new');

        $this->assertSame('new', $registry->get('key'));
        $this->assertCount(1, $registry->toArray());
    }

    public function testValuesCanBeRemoved(): void
    {
        $registry = new Registry($this->file);
        $registry->set('a', 1);
        $registry->set('b', 2);

        $registry->remove('a');

        $this->assertFalse($registry->has('a'));
        $this->assertSame(['b' => 2], $registry->toArray());
    }

    public function testRemovingAMissingKeyIsHarmless(): void
    {
        $registry = new Registry($this->file);
        $registry->set('a', 1);

        $registry->remove('missing');

        $this->assertSame(['a' => 1], $registry->toArray());
    }

    public function testSavingWritesJsonAndCreatesDirectories(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', ['nested' => true]);

        $registry->save();

        $this->assertFileExists($this->file);
        $this->assertSame(['key' => ['nested' => true]], json_decode(FileSystem::read($this->file), true));
    }

    public function testSavedValuesAreLoadedByOtherInstances(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', 'value');
        $registry->save();

        $other = new Registry($this->file);

        $this->assertSame('value', $other->get('key'));
        $this->assertSame(['key' => 'value'], $other->toArray());
    }

    public function testChangesAreSavedWhenTheRegistryIsDestroyed(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', 'value');

        unset($registry);

        $this->assertSame(['key' => 'value'], (new Registry($this->file))->toArray());
    }

    public function testUnchangedRegistriesAreNotRewrittenWhenDestroyed(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', 'value');
        $registry->save();
        unset($registry);
        $modified = FileSystem::lastModifiedTime($this->file);
        $contents = FileSystem::read($this->file);

        $reader = new Registry($this->file);
        $reader->has('key');
        FileSystem::write($this->file, $contents . ' ');
        unset($reader);

        $this->assertSame($contents . ' ', FileSystem::read($this->file), 'A registry that was only read must not overwrite the file');
        $this->assertGreaterThanOrEqual($modified, FileSystem::lastModifiedTime($this->file));
    }

    public function testRemovalsAreSaved(): void
    {
        $registry = new Registry($this->file);
        $registry->set('a', 1);
        $registry->set('b', 2);
        $registry->save();

        $registry->remove('a');
        $registry->save();

        $this->assertSame(['b' => 2], (new Registry($this->file))->toArray());
    }

    public function testExistingFilesAreLoadedBeforeBeingModified(): void
    {
        FileSystem::createDirectory(dirname($this->file));
        FileSystem::write($this->file, '{"existing":"kept"}');

        $registry = new Registry($this->file);
        $registry->set('added', 'new');
        $registry->save();

        $this->assertSame(['existing' => 'kept', 'added' => 'new'], json_decode(FileSystem::read($this->file), true));
    }

    public function testEmptyRegistriesAreSavedAsAnEmptyObjectOrList(): void
    {
        $registry = new Registry($this->file);
        $registry->set('a', 1);
        $registry->remove('a');
        $registry->save();

        $this->assertSame([], json_decode(FileSystem::read($this->file), true));
    }

    #[DataProvider('falseyValueProvider')]
    public function testFalseyValuesAreRegisteredValues(mixed $value): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', $value);

        $this->assertTrue($registry->has('key'));
        $this->assertSame($value, $registry->get('key'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function falseyValueProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'empty string' => [''];
        yield 'false' => [false];
        yield 'empty array' => [[]];
    }

    public function testNullValuesAreRegisteredValues(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', null);

        $this->assertTrue($registry->has('key'), 'A key set to null is still defined');
        $this->assertNull($registry->get('key'));
    }

    public function testNullValuesCanBeRemoved(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', null);

        $registry->remove('key');

        $this->assertArrayNotHasKey('key', $registry->toArray());
    }

    public function testNullValuesSurviveSaving(): void
    {
        $registry = new Registry($this->file);
        $registry->set('key', null);
        $registry->save();

        $this->assertTrue((new Registry($this->file))->has('key'));
    }

    public function testIntegerLikeKeysAreSupported(): void
    {
        $registry = new Registry($this->file);
        $registry->set('20250102', 5);
        $registry->save();

        $this->assertSame(5, (new Registry($this->file))->get('20250102'));
    }

    public function testInvalidJsonIsReported(): void
    {
        FileSystem::createDirectory(dirname($this->file));
        FileSystem::write($this->file, '{not json');

        $this->expectException(\Throwable::class);
        (new Registry($this->file))->toArray();
    }

    public function testFailedLoadsDoNotOverwriteTheFile(): void
    {
        FileSystem::createDirectory(dirname($this->file));
        FileSystem::write($this->file, '{not json');

        try {
            $registry = new Registry($this->file);
            $registry->set('key', 'value');
            $this->fail('Loading an invalid file should fail.');
        } catch (\Throwable) {
        }
        unset($registry);

        $this->assertSame('{not json', FileSystem::read($this->file));
    }
}
