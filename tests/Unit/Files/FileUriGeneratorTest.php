<?php

namespace Formwork\Tests\Unit\Files;

use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Files\File;
use Formwork\Files\FileUriGenerator;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileUriGenerator::class)]
final class FileUriGeneratorTest extends TestCase
{
    public function testGeneratesTheSiteFileRoute(): void
    {
        $config = $this->config();
        $uri = $this->createMock(UriGenerator::class);
        $uri->expects($this->once())->method('route')->with('files', ['name' => 'logo.png'])->willReturn('/files/logo.png');

        $this->assertSame('/files/logo.png', $this->generator($config, $uri)->generate(new File('/site/files/logo.png')));
    }

    public function testGeneratesTheProcessedImageRoute(): void
    {
        $config = $this->config();
        $uri = $this->createMock(UriGenerator::class);
        $uri->expects($this->once())->method('route')->with('assets', ['type' => 'images', 'id' => 'abc123', 'name' => 'thumb.jpg'])->willReturn('/assets/images/abc123/thumb.jpg');

        $this->assertSame('/assets/images/abc123/thumb.jpg', $this->generator($config, $uri)->generate(new File('/assets/images/abc123/thumb.jpg')));
    }

    public function testGeneratesPageContentPathsWithoutNumericDirectoryPrefixes(): void
    {
        $config = $this->config();
        $uri = $this->createMock(UriGenerator::class);
        $uri->expects($this->once())->method('path')->with('/about/team/team.txt')->willReturn('/about/team/team.txt');

        $this->assertSame('/about/team/team.txt', $this->generator($config, $uri)->generate(new File('/site/pages/1-about/2-team/team.txt')));
    }

    public function testGeneratesUserImageAndPanelAssetPaths(): void
    {
        $config = $this->config();
        $uri = $this->createMock(UriGenerator::class);
        $uri->expects($this->once())->method('path')->with('panel/assets/icons/menu.svg')->willReturn('/panel/assets/icons/menu.svg');
        $uri->expects($this->once())->method('route')->with('panel.users.images', ['image' => 'avatar.png'])->willReturn('/panel/users/images/avatar.png');

        $generator = $this->generator($config, $uri);
        $this->assertSame('/panel/users/images/avatar.png', $generator->generate(new File('/site/users/images/avatar.png')));
        $this->assertSame('/panel/assets/icons/menu.svg', $generator->generate(new File('/panel/assets/icons/menu.svg')));
    }

    public function testUnknownPathRaisesTheDomainException(): void
    {
        $generator = $this->generator($this->config(), $this->createStub(UriGenerator::class));

        $this->expectException(FileUriGenerationException::class);
        $this->expectExceptionMessage('missing file generator');
        $generator->generate(new File('/outside/file.txt'));
    }

    public function testAbsoluteUriResolvesTheGeneratedUriAgainstTheRequestUri(): void
    {
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/panel/current',
            'HTTP_HOST'   => 'example.test',
            'SERVER_NAME' => 'example.test',
            'SERVER_PORT' => '443',
            'HTTPS'       => 'on',
        ]);
        $uri = $this->createStub(UriGenerator::class);
        $generator = new FileUriGenerator($this->config(), $request, $uri);
        $file = new File('/site/files/logo.png');
        $uri->method('route')->willReturn('/files/logo.png');

        $this->assertSame('https://example.test/files/logo.png', $generator->generateAbsolute($file));
    }

    private function config(): Config
    {
        return new Config(['system' => [
            'files'  => ['paths' => ['site' => '/site/files']],
            'images' => ['processPath' => '/assets/images'],
            'pages'  => ['path' => '/site/pages'],
            'users'  => ['paths' => ['images' => '/site/users/images']],
            'panel'  => ['paths' => ['assets' => '/panel/assets']],
        ]], resolved: true);
    }

    private function generator(Config $config, UriGenerator $uri): FileUriGenerator
    {
        return new FileUriGenerator($config, new Request([], [], [], [], []), $uri);
    }
}
