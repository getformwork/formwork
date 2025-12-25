<?php

namespace Formwork\Statistics;

use Formwork\Http\Request;
use Formwork\Http\Utils\IpAnonymizer;
use Formwork\Http\Utils\Visitor;
use Formwork\Log\Registry;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use Generator;

final class Statistics
{
    /**
     * Date format
     */
    private const string DATE_FORMAT = 'Ymd';

    /**
     * Number of days displayed in the statistics chart
     */
    private const int DEFAULT_CHART_LIMIT = 7;

    /**
     * @var array<string, Registry>
     */
    private array $registries;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $options,
        private Request $request,
        private Translation $translation,
    ) {
        $path = $this->options['path'];

        if (!FileSystem::exists($path)) {
            FileSystem::createDirectory($path);
        }

        $this->loadRegistries($path, $this->options['registries']);
    }

    /**
     * Track a visit
     */
    public function trackVisit(): void
    {
        if ($this->request->isLocalhost() && !$this->options['trackLocalhost']) {
            return;
        }

        if (Visitor::isBot($this->request) || !$this->request->ip()) {
            return;
        }

        $ip = IpAnonymizer::anonymize($this->request->ip());
        $uri = Str::append(Uri::make(['query' => '', 'fragment' => ''], $this->request->uri()), '/');

        // Prefer speed over security for hashing, as it's not a security-critical operation
        $hash = hash('xxh3', "{$ip}@{$uri}");

        $timestamp = time();

        if (
            $this->registries['sessions']->has($hash)
            && $timestamp - $this->registries['sessions']->get($hash) < $this->options['visitsDelay']
        ) {
            $this->registries['sessions']->save();
            return;
        }

        $this->registries['sessions']->set($hash, $timestamp);

        $date = date(self::DATE_FORMAT, $timestamp);

        $todayVisits = $this->registries['visits']->has($date) ? (int) $this->registries['visits']->get($date) : 0;
        $this->registries['visits']->set($date, $todayVisits + 1);

        $todayUniqueVisits = $this->registries['uniqueVisits']->has($date) ? (int) $this->registries['uniqueVisits']->get($date) : 0;
        if (!$this->registries['visitors']->has($ip) || $this->registries['visitors']->get($ip) !== $date) {
            $this->registries['uniqueVisits']->set($date, $todayUniqueVisits + 1);
            $this->registries['uniqueVisits']->save();
        }

        $this->registries['visitors']->set($ip, $date);

        $pageViews = $this->registries['pageViews']->has($uri) ? (int) $this->registries['pageViews']->get($uri) : 0;
        $this->registries['pageViews']->set($uri, $pageViews + 1);

        if (($referer = $this->request->referer()) === null || ($source = Uri::host($referer)) !== $this->request->host()) {
            $source ??= '';
            $sourceVisits = $this->registries['sources']->has($source) ? (int) $this->registries['sources']->get($source) : 0;
            $this->registries['sources']->set($source, $sourceVisits + 1);
        }

        $device = Visitor::getDeviceType($this->request)->value;
        $deviceVisits = $this->registries['devices']->has($device) ? (int) $this->registries['devices']->get($device) : 0;
        $this->registries['devices']->set($device, $deviceVisits + 1);

        if (random_int(1, 100) <= $this->options['cleanup']['probability']) {
            $this->cleanupSessionsData();
            $this->cleanupVisitorsData();
        }

        $this->saveRegistries();
    }

    /**
     * Return chart data
     *
     * @return array{labels: array<string>, series: list<list<int>>}
     */
    public function getChartData(int $limit = self::DEFAULT_CHART_LIMIT): array
    {

        $visits = $this->getVisits($limit);
        $uniqueVisits = $this->getUniqueVisits($limit);

        $labels = Arr::map(
            iterator_to_array($this->generateDays($limit)),
            fn(string $day): string => Date::formatTimestamp(Date::toTimestamp($day, self::DATE_FORMAT), "D\nj M", $this->translation)
        );

        return [
            'labels' => $labels,
            'series' => [
                array_values($visits),
                array_values($uniqueVisits),
            ],
        ];
    }

    /**
     * Return page views
     *
     * @return array<string, int>
     */
    public function getPageViews(): array
    {
        return Arr::sort($this->registries['pageViews']->toArray(), SORT_DESC);
    }

    /**
     * Return visits by source
     *
     * @return array<string, int>
     */
    public function getSources(): array
    {
        return Arr::sort($this->registries['sources']->toArray(), SORT_DESC);
    }

    /**
     * Return visits by devices
     *
     * @return array<string, int>
     */
    public function getDevices(): array
    {
        return Arr::sort($this->registries['devices']->toArray(), SORT_DESC);
    }

    /**
     * Return visits
     *
     * @return array<string, int>
     */
    public function getVisits(int $limit = self::DEFAULT_CHART_LIMIT): array
    {
        return $this->interpolateVisits($this->registries['visits']->toArray(), $limit);
    }

    /**
     * Return unique visits
     *
     * @return array<string, int>
     */
    public function getUniqueVisits(int $limit = self::DEFAULT_CHART_LIMIT): array
    {
        return $this->interpolateVisits($this->registries['uniqueVisits']->toArray(), $limit);
    }

    /**
     * Load statistics registries
     *
     * @param array<string, string> $registries
     */
    private function loadRegistries(string $path, array $registries): void
    {
        foreach ($registries as $name => $filename) {
            $this->registries[$name] = new Registry(FileSystem::joinPaths($path, basename($filename)));
        }
    }

    /**
     * Save statistics registries
     */
    private function saveRegistries(): void
    {
        foreach ($this->registries as $registry) {
            $registry->save();
        }
    }

    /**
     * Interpolate visits
     *
     * @param array<string, int> $visits
     *
     * @return array<string, int>
     */
    private function interpolateVisits(array $visits, int $limit): array
    {
        $result = [];
        foreach ($this->generateDays($limit) as $day) {
            $result[$day] = $visits[$day] ?? 0;
        }
        return $result;
    }

    /**
     * Generate days
     *
     * @return Generator<int, string>
     */
    private function generateDays(int $limit): Generator
    {
        $low = time() - ($limit - 1) * 86400;
        for ($i = 0; $i < $limit; $i++) {
            yield date(self::DATE_FORMAT, $low + $i * 86400);
        }
    }

    /**
     * Cleanup visitors data
     */
    private function cleanupVisitorsData(): void
    {
        $today = date(self::DATE_FORMAT, time());
        foreach ($this->registries['visitors']->toArray() as $key => $date) {
            if ($date !== $today) {
                $this->registries['visitors']->remove($key);
            }
        }
    }

    /**
     * Cleanup sessions data prior to ttl
     */
    private function cleanupSessionsData(): void
    {
        $time = time();
        foreach ($this->registries['sessions']->toArray() as $key => $timestamp) {
            if ($time - $timestamp > $this->options['cleanup']['ttl']) {
                $this->registries['sessions']->remove($key);
            }
        }
    }
}
