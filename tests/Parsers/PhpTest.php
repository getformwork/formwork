<?php

namespace Formwork\Tests\Parsers;

use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Parsers\Php;
use Formwork\Tests\Parsers\Fixtures\EnumFixture;
use Formwork\Tests\Parsers\Fixtures\SerializableFixture;
use Formwork\Tests\Parsers\Fixtures\SetStateImplementingClassFixture;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Php::class)]
final class PhpTest extends TestCase
{
    protected function setUp(): void
    {
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/files/php', TESTS_TMP_PATH, overwrite: true);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testParseAlwaysThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Parsing a string of Php code is not allowed');
        Php::parse('<?php echo "Hello, World!";');
    }

    public function testParseFile(): void
    {
        $filePath = TESTS_TMP_PATH . '/test.php';

        $this->assertSame([
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'testing', 'formwork'],
        ], Php::parseFile($filePath));
    }

    public function testEncodeExportsDataAsPhpString(): void
    {
        $enumFixtureClass = EnumFixture::class;

        $arraySerializable = $this->createStub(ArraySerializable::class);
        $arraySerializable->method('toArray')->willReturn([
            'key' => 'value',
        ]);
        $arraySerializableClass = $arraySerializable::class;

        $setStateImplementingClass = SetStateImplementingClassFixture::class;

        $serializable = new SerializableFixture('value');
        $serialized = addcslashes(serialize($serializable), '\\');

        $data = [
            'string'               => 'Test',
            'int'                  => 3,
            'float'                => 3.14,
            'boolean'              => false,
            'null'                 => null,
            'array'                => ['test', 29, 2.71, true, null, []],
            'object'               => (object) ['key' => 'value'],
            'enum'                 => EnumFixture::Alpha,
            'arraySerializable'    => $arraySerializable,
            'setStateImplementing' => new SetStateImplementingClassFixture('value'),
            'serializable'         => $serializable,
        ];

        $expected = <<<PHP
            [
                'string' => 'Test',
                'int' => 3,
                'float' => 3.14,
                'boolean' => false,
                'null' => null,
                'array' => [
                    'test',
                    29,
                    2.71,
                    true,
                    null,
                    []
                ],
                'object' => (object) [
                    'key' => 'value'
                ],
                'enum' => \\$enumFixtureClass::Alpha,
                'arraySerializable' => \\$arraySerializableClass::fromArray([
                    'key' => 'value'
                ]),
                'setStateImplementing' => \\$setStateImplementingClass::__set_state([
                    'key' => 'value'
                ]),
                'serializable' => unserialize('$serialized')
            ]
            PHP;

        $this->assertSame($expected, Php::encode($data));
    }

    public function testEncodeThrowsExceptionWithUnencodableClasses(): void
    {
        $data = [
            'closure' => fn() => 'Unencodable',
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Objects of class "Closure" cannot be encoded');
        Php::encode($data);
    }

    public function testEncodeThrowsExceptionWithResources(): void
    {
        $stream = fopen('php://temp', 'r');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Data of type "resource" cannot be encoded');

        try {
            Php::encode(['resource' => $stream]);
        } finally {
            fclose($stream);
        }
    }

    public function testEncodeToFile(): void
    {
        $data = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'testing', 'formwork'],
        ];

        $filePath = TESTS_TMP_PATH . '/output.php';

        Php::encodeToFile($data, $filePath);

        $this->assertSame($data, include $filePath);
    }
}
