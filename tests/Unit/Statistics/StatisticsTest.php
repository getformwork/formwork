<?php

namespace Formwork\Tests\Unit\Statistics;

use Formwork\Http\Request;
use Formwork\Http\Utils\IpAnonymizer;
use Formwork\Log\Registry;
use Formwork\Statistics\Statistics;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Statistics::class)]
final class StatisticsTest extends TestCase
{
    private const string DESKTOP = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36';

    private const string MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->path = FileSystem::joinPaths(TESTS_TMP_PATH, 'statistics');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testMissingDirectoryIsCreated(): void
    {
        $this->assertDirectoryDoesNotExist($this->path);

        $this->statistics();

        $this->assertDirectoryExists($this->path);
    }

    public function testExistingDirectoryIsReused(): void
    {
        FileSystem::createDirectory($this->path);
        FileSystem::write(FileSystem::joinPaths($this->path, 'marker.txt'), 'kept');

        $this->statistics();

        $this->assertFileExists(FileSystem::joinPaths($this->path, 'marker.txt'));
    }

    public function testEmptyStatisticsHaveNoData(): void
    {
        $statistics = $this->statistics();

        $this->assertSame([], $statistics->getPageViews());
        $this->assertSame([], $statistics->getSources());
        $this->assertSame([], $statistics->getDevices());
        $this->assertSame(array_fill_keys($this->lastDays(7), 0), $statistics->getVisits());
        $this->assertSame(array_fill_keys($this->lastDays(7), 0), $statistics->getUniqueVisits());
    }

    public function testVisitIsTrackedInEveryRegistry(): void
    {
        $statistics = $this->statistics();

        $statistics->trackVisit();

        $today = date('Ymd');
        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());
        $this->assertSame(1, $statistics->getVisits(1)[$today]);
        $this->assertSame(1, $statistics->getUniqueVisits(1)[$today]);
        $this->assertSame(['search.test' => 1], $statistics->getSources());
        $this->assertSame(['desktop' => 1], $statistics->getDevices());
    }

    public function testTrackedVisitsArePersisted(): void
    {
        $this->statistics()->trackVisit();

        $statistics = $this->statistics();

        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());
        $this->assertSame(1, $statistics->getVisits(1)[date('Ymd')]);
    }

    public function testQueryStringAndFragmentAreNotPartOfThePageKey(): void
    {
        $this->statistics(server: ['REQUEST_URI' => '/blog/post?utm_source=newsletter&page=2'])->trackVisit();

        $this->assertSame(['/blog/post/' => 1], $this->statistics()->getPageViews());
    }

    public function testRepeatedVisitsWithinTheDelayAreCountedOnce(): void
    {
        $statistics = $this->statistics();

        $statistics->trackVisit();
        $statistics->trackVisit();
        $this->statistics()->trackVisit();

        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());
        $this->assertSame(1, $statistics->getVisits(1)[date('Ymd')]);
    }

    public function testRepeatedVisitsAfterTheDelayAreCountedButTheVisitorIsUnique(): void
    {
        $statistics = $this->statistics(options: ['visitsDelay' => 0]);

        $statistics->trackVisit();
        $statistics->trackVisit();
        $statistics->trackVisit();

        $this->assertSame(['/blog/post/' => 3], $statistics->getPageViews());
        $this->assertSame(3, $statistics->getVisits(1)[date('Ymd')]);
        $this->assertSame(1, $statistics->getUniqueVisits(1)[date('Ymd')]);
    }

    public function testDifferentPagesOfTheSameVisitorAreCountedAsOneUniqueVisit(): void
    {
        $this->statistics(server: ['REQUEST_URI' => '/one'])->trackVisit();
        $this->statistics(server: ['REQUEST_URI' => '/two'])->trackVisit();

        $statistics = $this->statistics();

        $this->assertSame(['/one/' => 1, '/two/' => 1], $statistics->getPageViews());
        $this->assertSame(2, $statistics->getVisits(1)[date('Ymd')]);
        $this->assertSame(1, $statistics->getUniqueVisits(1)[date('Ymd')]);
    }

    public function testDifferentVisitorsAreCountedSeparately(): void
    {
        $this->statistics(server: ['REMOTE_ADDR' => '203.0.113.7'])->trackVisit();
        $this->statistics(server: ['REMOTE_ADDR' => '198.51.100.9'])->trackVisit();
        $this->statistics(server: ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_USER_AGENT' => self::MOBILE])->trackVisit();

        $statistics = $this->statistics();

        $this->assertSame(3, $statistics->getVisits(1)[date('Ymd')]);
        $this->assertSame(3, $statistics->getUniqueVisits(1)[date('Ymd')]);
        $this->assertSame(['/blog/post/' => 3], $statistics->getPageViews());
    }

    public function testVisitorsOnTheSameNetworkShareTheAnonymizedAddress(): void
    {
        $this->statistics(server: ['REMOTE_ADDR' => '203.0.113.7'])->trackVisit();
        $this->statistics(server: ['REMOTE_ADDR' => '203.0.113.200'])->trackVisit();

        $this->assertSame(1, $this->statistics()->getVisits(1)[date('Ymd')]);
    }

    public function testRawAddressesAreNeverStored(): void
    {
        $this->statistics(server: ['REMOTE_ADDR' => '203.0.113.7'])->trackVisit();

        foreach (FileSystem::listFiles($this->path) as $file) {
            $this->assertStringNotContainsString('203.0.113.7', FileSystem::read(FileSystem::joinPaths($this->path, $file)), $file);
        }
    }

    public function testLocalhostVisitsAreIgnoredByDefault(): void
    {
        $statistics = $this->statistics(server: ['REMOTE_ADDR' => '127.0.0.1']);

        $statistics->trackVisit();

        $this->assertSame([], $statistics->getPageViews());
        $this->assertSame(0, $statistics->getVisits(1)[date('Ymd')]);
    }

    public function testLocalhostVisitsCanBeTracked(): void
    {
        $statistics = $this->statistics(server: ['REMOTE_ADDR' => '127.0.0.1'], options: ['trackLocalhost' => true]);

        $statistics->trackVisit();

        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());
    }

    #[DataProvider('botProvider')]
    public function testBotsAreIgnored(string $userAgent): void
    {
        $statistics = $this->statistics(server: ['HTTP_USER_AGENT' => $userAgent]);

        $statistics->trackVisit();

        $this->assertSame([], $statistics->getPageViews());
        $this->assertSame(0, $statistics->getVisits(1)[date('Ymd')]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function botProvider(): iterable
    {
        yield 'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
        yield 'Bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'];
        yield 'curl' => ['curl/8.0.1'];
    }

    public function testVisitsWithoutAnAddressAreIgnored(): void
    {
        $statistics = $this->statistics(server: ['REMOTE_ADDR' => null]);

        $statistics->trackVisit();

        $this->assertSame([], $statistics->getPageViews());
    }

    public function testExternalRefererIsTrackedAsSource(): void
    {
        $this->statistics(server: ['HTTP_REFERER' => 'https://Search.Example:8443/results?q=1'])->trackVisit();

        $this->assertSame(['search.example' => 1], $this->statistics()->getSources());
    }

    public function testInternalRefererIsNotTrackedAsSource(): void
    {
        $statistics = $this->statistics(server: ['HTTP_REFERER' => 'https://example.test/previous']);

        $statistics->trackVisit();

        $this->assertSame([], $statistics->getSources());
        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());
    }

    public function testDirectVisitsAreCountedWithAnEmptySource(): void
    {
        $this->statistics(server: ['HTTP_REFERER' => null])->trackVisit();

        $this->assertSame(['' => 1], $this->statistics()->getSources());
    }

    /**
     * Sources are rendered in the panel, so only valid host names can be stored
     */
    #[DataProvider('hostileRefererProvider')]
    public function testHostileRefererCannotInjectMarkupIntoTheSources(string $referer): void
    {
        $statistics = $this->statistics(server: ['HTTP_REFERER' => $referer]);

        $statistics->trackVisit();

        $this->assertSame([], $statistics->getSources());
        $this->assertSame(['/blog/post/' => 1], $statistics->getPageViews());

        foreach (FileSystem::listFiles($this->path) as $file) {
            $content = FileSystem::read(FileSystem::joinPaths($this->path, $file));
            $this->assertStringNotContainsString('<script', $content, $file);
            $this->assertStringNotContainsString('onerror', $content, $file);
            $this->assertStringNotContainsString('onmouseover', $content, $file);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileRefererProvider(): iterable
    {
        yield 'script element in the host' => ['https://<script>alert(1)</script>.test/'];
        yield 'script element closing an attribute' => ['https://"><script>alert(1)</script>/'];
        yield 'image with an error handler' => ['https://a.test<img src=x onerror=alert(1)>/'];
        yield 'attribute injection' => ['https://evil.test"onmouseover="alert(1)/'];
        yield 'null byte' => ["https://evil.test\x00.example/"];
        yield 'space' => ['https://exa mple.test/'];
        yield 'underscore' => ['https://a_b.test/'];
        yield 'not a URI' => ['not a uri'];
        yield 'script URI' => ['javascript:alert(1)'];
    }

    public function testDeviceTypesAreCounted(): void
    {
        $this->statistics(server: ['HTTP_USER_AGENT' => self::DESKTOP, 'REMOTE_ADDR' => '203.0.113.1'])->trackVisit();
        $this->statistics(server: ['HTTP_USER_AGENT' => self::MOBILE, 'REMOTE_ADDR' => '203.0.113.2'])->trackVisit();
        $this->statistics(server: ['HTTP_USER_AGENT' => self::MOBILE, 'REMOTE_ADDR' => '198.51.100.3'])->trackVisit();

        $this->assertSame(['mobile' => 2, 'desktop' => 1], $this->statistics()->getDevices());
    }

    public function testPageViewsAreSortedByViewsDescending(): void
    {
        foreach (['/rare' => 1, '/popular' => 3, '/common' => 2] as $uri => $views) {
            for ($i = 0; $i < $views; $i++) {
                $this->statistics(server: ['REQUEST_URI' => $uri, 'REMOTE_ADDR' => "203.0.$i.1"])->trackVisit();
            }
        }

        $this->assertSame(['/popular/' => 3, '/common/' => 2, '/rare/' => 1], $this->statistics()->getPageViews());
    }

    public function testSourcesAreSortedByVisitsDescending(): void
    {
        $this->statistics(server: ['HTTP_REFERER' => 'https://one.test/', 'REMOTE_ADDR' => '203.0.1.1'])->trackVisit();
        $this->statistics(server: ['HTTP_REFERER' => 'https://two.test/', 'REMOTE_ADDR' => '203.0.2.1'])->trackVisit();
        $this->statistics(server: ['HTTP_REFERER' => 'https://two.test/', 'REMOTE_ADDR' => '203.0.3.1'])->trackVisit();

        $this->assertSame(['two.test' => 2, 'one.test' => 1], $this->statistics()->getSources());
    }

    public function testVisitsCoverTheRequestedNumberOfDaysEndingToday(): void
    {
        $statistics = $this->statistics();

        foreach ([1, 3, 7, 30] as $limit) {
            $visits = $statistics->getVisits($limit);

            // Numeric day keys are cast to integers by PHP
            $this->assertSame($this->lastDays($limit), array_map('strval', array_keys($visits)), "Limit $limit");
            $this->assertSame((int) date('Ymd'), array_key_last($visits));
        }
    }

    public function testStoredVisitsOfPreviousDaysAreReturnedAndMissingDaysAreZero(): void
    {
        $days = $this->lastDays(5);
        $this->writeRegistry('visits.json', [$days[1] => 10, $days[3] => 4, '19990101' => 99]);
        $this->writeRegistry('uniqueVisits.json', [$days[1] => 6]);

        $statistics = $this->statistics();

        $this->assertSame([$days[0] => 0, $days[1] => 10, $days[2] => 0, $days[3] => 4, $days[4] => 0], $statistics->getVisits(5));
        $this->assertSame([$days[0] => 0, $days[1] => 6, $days[2] => 0, $days[3] => 0, $days[4] => 0], $statistics->getUniqueVisits(5));
    }

    public function testChartDataContainsLabelsAndTheVisitSeries(): void
    {
        $days = $this->lastDays(3);
        $this->writeRegistry('visits.json', [$days[0] => 5, $days[2] => 2]);
        $this->writeRegistry('uniqueVisits.json', [$days[0] => 3, $days[2] => 1]);

        $chart = $this->statistics()->getChartData(3);

        $this->assertSame([[5, 0, 2], [3, 0, 1]], $chart['series']);
        $this->assertCount(3, $chart['labels']);
        $this->assertSame(
            date('D', (int) strtotime($days[2])) . "\n" . date('j', (int) strtotime($days[2])) . ' ' . date('M', (int) strtotime($days[2])),
            $chart['labels'][2],
        );
    }

    public function testChartDataUsesTheDefaultNumberOfDays(): void
    {
        $chart = $this->statistics()->getChartData();

        $this->assertCount(7, $chart['labels']);
        $this->assertCount(7, $chart['series'][0]);
        $this->assertCount(7, $chart['series'][1]);
    }

    public function testExpiredSessionsAreRemovedByTheCleanup(): void
    {
        $this->writeRegistry('sessions.json', ['expired' => time() - 100000, 'recent' => time() - 10]);

        $this->statistics(options: ['cleanup' => ['ttl' => 3600, 'probability' => 100]])->trackVisit();

        $sessions = $this->readRegistry('sessions.json');
        $this->assertArrayNotHasKey('expired', $sessions);
        $this->assertArrayHasKey('recent', $sessions);
        $this->assertCount(2, $sessions);
    }

    public function testVisitorsOfPreviousDaysAreRemovedByTheCleanup(): void
    {
        $this->writeRegistry('visitors.json', ['old-visitor' => '19990101']);

        $this->statistics(options: ['cleanup' => ['ttl' => 3600, 'probability' => 100]])->trackVisit();

        $visitors = $this->readRegistry('visitors.json');
        $this->assertArrayNotHasKey('old-visitor', $visitors);
        $this->assertSame([date('Ymd')], array_values($visitors));
    }

    public function testCleanupIsSkippedWhenTheProbabilityIsZero(): void
    {
        $this->writeRegistry('sessions.json', ['expired' => time() - 100000]);
        $this->writeRegistry('visitors.json', ['old-visitor' => '19990101']);

        $this->statistics(options: ['cleanup' => ['ttl' => 3600, 'probability' => 0]])->trackVisit();

        $this->assertArrayHasKey('expired', $this->readRegistry('sessions.json'));
        $this->assertArrayHasKey('old-visitor', $this->readRegistry('visitors.json'));
    }

    public function testLegacyVisitorKeysBasedOnTheAddressAreMigrated(): void
    {
        $ip = IpAnonymizer::anonymize('203.0.113.7');
        $this->writeRegistry('visitors.json', [$ip => date('Ymd')]);

        $this->statistics()->trackVisit();

        $visitors = $this->readRegistry('visitors.json');
        $this->assertArrayNotHasKey($ip, $visitors);
        $this->assertCount(1, $visitors);
        // The visitor was already counted today under the legacy key, so no new unique visit is recorded
        $this->assertSame(0, $this->statistics()->getUniqueVisits(1)[date('Ymd')]);
        $this->assertSame(1, $this->statistics()->getVisits(1)[date('Ymd')]);
    }

    public function testRegistryFilesCannotEscapeTheStatisticsDirectory(): void
    {
        $registries = $this->registries();
        $registries['visits'] = '../../outside.json';

        $this->statistics(options: ['registries' => $registries])->trackVisit();

        $this->assertFileDoesNotExist(FileSystem::joinPaths(TESTS_TMP_PATH, 'outside.json'));
        $this->assertFileDoesNotExist(dirname(TESTS_TMP_PATH) . '/outside.json');
        $this->assertFileExists(FileSystem::joinPaths($this->path, 'outside.json'));
    }

    /**
     * @param array<string, string|null> $server
     * @param array<string, mixed>       $options
     */
    private function statistics(array $server = [], array $options = []): Statistics
    {
        $server += [
            'REQUEST_METHOD'  => 'GET',
            'SERVER_NAME'     => 'example.test',
            'SERVER_PORT'     => '80',
            'REMOTE_ADDR'     => '203.0.113.7',
            'REQUEST_URI'     => '/blog/post',
            'HTTP_USER_AGENT' => self::DESKTOP,
            'HTTP_REFERER'    => 'https://search.test/results',
        ];

        $request = new Request([], [], [], [], array_filter($server, static fn(?string $value): bool => $value !== null));

        return new Statistics(
            $options + [
                'path'           => $this->path,
                'trackLocalhost' => false,
                'visitsDelay'    => 15,
                'registries'     => $this->registries(),
                'cleanup'        => ['ttl' => 86400, 'probability' => 0],
            ],
            $request,
            new Translation('en', [
                'date.weekdays.short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                'date.months.short'   => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            ]),
        );
    }

    /**
     * @return array<string, string>
     */
    private function registries(): array
    {
        return [
            'sessions'     => 'sessions.json',
            'visits'       => 'visits.json',
            'uniqueVisits' => 'uniqueVisits.json',
            'visitors'     => 'visitors.json',
            'pageViews'    => 'pageViews.json',
            'sources'      => 'sources.json',
            'devices'      => 'devices.json',
        ];
    }

    /**
     * @return list<string>
     */
    private function lastDays(int $limit): array
    {
        $days = [];
        for ($i = $limit - 1; $i >= 0; $i--) {
            $days[] = date('Ymd', time() - $i * 86400);
        }
        return $days;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeRegistry(string $filename, array $data): void
    {
        $registry = new Registry(FileSystem::joinPaths($this->path, $filename));
        foreach ($data as $key => $value) {
            $registry->set((string) $key, $value);
        }
        $registry->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function readRegistry(string $filename): array
    {
        return (new Registry(FileSystem::joinPaths($this->path, $filename)))->toArray();
    }
}
