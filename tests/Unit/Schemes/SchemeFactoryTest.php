<?php

namespace Formwork\Tests\Unit\Schemes;

use Formwork\Schemes\Scheme;
use Formwork\Schemes\SchemeFactory;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemeFactory::class)]
final class SchemeFactoryTest extends TestCase
{
    public function testSchemeIsBuiltThroughTheContainerWithIdAndData(): void
    {
        $scheme = $this->createStub(Scheme::class);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Scheme::class, ['id' => 'pages.post', 'data' => ['title' => 'Post']])
            ->willReturn($scheme);

        $this->assertSame($scheme, (new SchemeFactory($container))->make('pages.post', ['title' => 'Post']));
    }

    public function testDataDefaultsToAnEmptyArray(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Scheme::class, ['id' => 'files.file', 'data' => []])
            ->willReturn($this->createStub(Scheme::class));

        (new SchemeFactory($container))->make('files.file');
    }

    public function testAllowTagsOptionIsMappedToAllowTaxonomyWithADeprecation(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Scheme::class, ['id' => 'pages.post', 'data' => ['options' => ['allowTags' => true, 'allowTaxonomy' => true]]])
            ->willReturn($this->createStub(Scheme::class));

        $messages = $this->captureDeprecations(fn() => (new SchemeFactory($container))->make('pages.post', ['options' => ['allowTags' => true]]));

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('allowTags', $messages[0]);
    }

    public function testAllowTagsMappingOverridesAnExistingAllowTaxonomy(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Scheme::class, $this->callback(static fn(array $arguments): bool => $arguments['data']['options']['allowTaxonomy'] === false))
            ->willReturn($this->createStub(Scheme::class));

        $this->captureDeprecations(fn() => (new SchemeFactory($container))->make('pages.post', ['options' => ['allowTags' => false, 'allowTaxonomy' => true]]));
    }

    public function testAllowTagsIsIgnoredForNonPageSchemes(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Scheme::class, ['id' => 'files.file', 'data' => ['options' => ['allowTags' => true]]])
            ->willReturn($this->createStub(Scheme::class));

        $messages = $this->captureDeprecations(fn() => (new SchemeFactory($container))->make('files.file', ['options' => ['allowTags' => true]]));

        $this->assertSame([], $messages);
    }

    public function testNoDeprecationWithoutTheLegacyOption(): void
    {
        $container = $this->createStub(Container::class);
        $container->method('build')->willReturn($this->createStub(Scheme::class));

        $messages = $this->captureDeprecations(fn() => (new SchemeFactory($container))->make('pages.post', ['options' => ['allowTaxonomy' => true]]));

        $this->assertSame([], $messages);
    }
}
