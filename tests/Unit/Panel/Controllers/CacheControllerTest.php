<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cache\ArrayCache;
use Formwork\Cms\Site;
use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\CacheController;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

#[CoversClass(CacheController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class CacheControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private ArrayCache $pagesCache;

    private string $imagesPath;

    private string $contentPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pagesCache = new ArrayCache('pages');
        $this->pagesCache->set('page', 'cached');

        $this->imagesPath = TESTS_TMP_PATH . '/images';
        FileSystem::createDirectory($this->imagesPath);
        FileSystem::write($this->imagesPath . '/processed.jpg', 'image');

        $this->contentPath = TESTS_TMP_PATH . '/content';
        FileSystem::createDirectory($this->contentPath);
        touch($this->contentPath, 1000);
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testClearingOnlyClearsPagesByDefault(): void
    {
        $response = $this->controller()->clear(new RouteParams([]));

        $this->assertSuccess($response, 'default');
        $this->assertFalse($this->pagesCache->has('page'));
        $this->assertFileExists($this->imagesPath . '/processed.jpg');
    }

    public function testClearingAlsoClearsImagesWhenConfigured(): void
    {
        $response = $this->controller(system: ['images' => ['clearCacheByDefault' => true]])->clear(new RouteParams([]));

        $this->assertSuccess($response, 'default');
        $this->assertFalse($this->pagesCache->has('page'));
        $this->assertFileDoesNotExist($this->imagesPath . '/processed.jpg');
    }

    public function testPagesCacheCanBeCleared(): void
    {
        $response = $this->controller()->clear(new RouteParams(['type' => 'pages']));

        $this->assertSuccess($response, 'pages');
        $this->assertFalse($this->pagesCache->has('page'));
        $this->assertFileExists($this->imagesPath . '/processed.jpg');
    }

    public function testImagesCacheCanBeCleared(): void
    {
        $response = $this->controller()->clear(new RouteParams(['type' => 'images']));

        $this->assertSuccess($response, 'images');
        $this->assertTrue($this->pagesCache->has('page'));
        $this->assertDirectoryExists($this->imagesPath);
        $this->assertFileDoesNotExist($this->imagesPath . '/processed.jpg');
    }

    public function testAllCachesCanBeCleared(): void
    {
        $response = $this->controller()->clear(new RouteParams(['type' => 'all']));

        $this->assertSuccess($response, 'all');
        $this->assertFalse($this->pagesCache->has('page'));
        $this->assertDirectoryExists($this->imagesPath);
        $this->assertFileDoesNotExist($this->imagesPath . '/processed.jpg');
    }

    public function testConfigCacheCanBeCleared(): void
    {
        $response = $this->controller()->clear(new RouteParams(['type' => 'config']));

        $this->assertSuccess($response, 'config');
        $this->assertTrue($this->pagesCache->has('page'));
        $this->assertFileExists($this->imagesPath . '/processed.jpg');
    }

    #[DataProvider('invalidTypeProvider')]
    public function testUnknownTypesAreRejectedWithoutClearingAnything(string $type): void
    {
        $response = $this->controller()->clear(new RouteParams(['type' => $type]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::BadRequest, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
        $this->assertTrue($this->pagesCache->has('page'));
        $this->assertFileExists($this->imagesPath . '/processed.jpg');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTypeProvider(): iterable
    {
        yield 'unknown' => ['bogus'];
        yield 'empty' => [''];
        yield 'different case' => ['PAGES'];
        yield 'path traversal' => ['../images'];
    }

    public function testClearingTouchesTheContentDirectoryToInvalidateDerivedCaches(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('contentPath')->willReturn($this->contentPath);

        $this->controller(services: [Site::class => $site])->clear(new RouteParams(['type' => 'pages']));

        clearstatcache();
        $this->assertGreaterThan(1000, filemtime($this->contentPath));
    }

    public function testImagesClearingDoesNotTouchTheContentDirectory(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('contentPath')->willReturn($this->contentPath);

        $this->controller(services: [Site::class => $site])->clear(new RouteParams(['type' => 'images']));

        clearstatcache();
        $this->assertSame(1000, filemtime($this->contentPath));
    }

    public function testClearingIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('Cache cleared'), $this->callback(fn(array $context): bool => $context['types'] === 'pages, images, config'));

        $this->controller(services: [LoggerInterface::class => $logger])->clear(new RouteParams(['type' => 'all']));
    }

    public function testRejectedClearingIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('notice');

        $this->controller(services: [LoggerInterface::class => $logger])->clear(new RouteParams(['type' => 'bogus']));
    }

    private function assertSuccess(mixed $response, string $type): void
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());

        $data = Json::parse($response->content());

        $this->assertSame('success', $data['status']);
        $this->assertSame($type, $data['data']['type']);
    }

    /**
     * @param array<string, mixed>  $system
     * @param array<string, object> $services
     */
    private function controller(array $system = [], array $services = []): CacheController
    {
        return $this->makeController(
            CacheController::class,
            system: array_replace_recursive(['images' => ['processPath' => $this->imagesPath]], $system),
            services: ['cache.pages' => $this->pagesCache, ...$services],
        );
    }
}
