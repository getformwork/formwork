<?php

namespace Formwork\Tests\Unit\Templates;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Schemes\Scheme;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Templates\Template;
use Formwork\Templates\TemplateFactory;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TemplateFactory::class)]
final class TemplateFactoryTest extends TestCase
{
    public function testTemplateIsBuiltWithSchemeVariablesAndTemplatesPath(): void
    {
        $scheme = $this->createStub(Scheme::class);
        $schemes = $this->createMock(Schemes::class);
        $schemes->expects($this->once())->method('get')->with('pages.post')->willReturn($scheme);

        $template = $this->createStub(Template::class);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('build')
            ->with(Template::class, $this->callback(function (array $parameters) use ($scheme): bool {
                $this->assertSame('post', $parameters['name']);
                $this->assertSame('/templates/path', $parameters['path']);
                $this->assertSame([], $parameters['methods']);
                $this->assertSame($scheme, $parameters['scheme']);
                $this->assertSame(App::instance(), $parameters['vars']['app']);
                $this->assertSame(App::instance()->router(), $parameters['vars']['router']);
                $this->assertSame(App::instance()->site(), $parameters['vars']['site']);
                $this->assertInstanceOf(CsrfToken::class, $parameters['vars']['csrfToken']);
                return true;
            }))
            ->willReturn($template);

        $factory = new TemplateFactory($container, App::instance(), $this->config('/templates/path'), $schemes);

        $this->assertSame($template, $factory->make('post'));
    }

    public function testUnknownSchemesAreReported(): void
    {
        $schemes = $this->createStub(Schemes::class);
        $schemes->method('get')->willThrowException(new InvalidArgumentException('Invalid scheme "pages.ghost"'));

        $factory = new TemplateFactory($this->createStub(Container::class), App::instance(), $this->config('/templates/path'), $schemes);

        $this->expectException(InvalidArgumentException::class);

        $factory->make('ghost');
    }

    private function config(string $templatesPath): Config
    {
        return new Config(['system' => ['templates' => ['path' => $templatesPath]]], resolved: true);
    }
}
