<?php

namespace Formwork\Statistics;

use Formwork\App;
use Formwork\Http\Request;
use Formwork\Http\Utils\IpAnonymizer;
use Formwork\Http\Utils\Visitor;
use Formwork\Log\Registry;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Generator;

class Statistics
{
    /**
     * Date format
     */
    protected const DATE_FORMAT = 'Ymd';

    /**
     * Number of days displayed in the statistics chart
     */
    protected const CHART_LIMIT = 7;

    /**
     * Visits registry filename
     */
    protected const VISITS_FILENAME = 'visits.json';

    /**
     * Unique visits registry filename
     */
    protected const UNIQUE_VISITS_FILENAME = 'uniqueVisits.json';

    /**
     * Visitors registry filename
     */
    protected const VISITORS_FILENAME = 'visitors.json';

    /**
     * Page views registry filename
     */
    protected const PAGE_VIEWS_FILENAME = 'pageViews.json';

    /**
     * Visits registry
     */
    protected Registry $visitsRegistry;

    /**
     * Unique visits registry
     */
    protected Registry $uniqueVisitsRegistry;

    /**
     * Visitors registry
     */
    protected Registry $visitorsRegistry;

    /**
     * Page views registry
     */
    protected Registry $pageViewsRegistry;

    /**
     * Create a new Statistics instance
     */
    public function __construct(string $path, protected App $app, protected Request $request)
    {
        if (!FileSystem::exists($path)) {
            FileSystem::createDirectory($path);
        }

        $this->visitsRegistry = new Registry(FileSystem::joinPaths($path, self::VISITS_FILENAME));
        $this->uniqueVisitsRegistry = new Registry(FileSystem::joinPaths($path, self::UNIQUE_VISITS_FILENAME));
        $this->visitorsRegistry = new Registry(FileSystem::joinPaths($path, self::VISITORS_FILENAME));
        $this->pageViewsRegistry = new Registry(FileSystem::joinPaths($path, self::PAGE_VIEWS_FILENAME));
    }

    /**
     * Track a visit
     */
    public function trackVisit(): void
    {
        if (Visitor::isBot($this->request) || !Visitor::isTrackable($this->request) || !$this->request->ip()) {
            return;
        }

        $date = date(self::DATE_FORMAT);
        $ip = IpAnonymizer::anonymize($this->request->ip());

        $todayVisits = $this->visitsRegistry->has($date) ? (int) $this->visitsRegistry->get($date) : 0;
        $this->visitsRegistry->set($date, $todayVisits + 1);
        $this->visitsRegistry->save();

        $todayUniqueVisits = $this->uniqueVisitsRegistry->has($date) ? (int) $this->uniqueVisitsRegistry->get($date) : 0;
        if (!$this->visitorsRegistry->has($ip) || $this->visitorsRegistry->get($ip) !== $date) {
            $this->uniqueVisitsRegistry->set($date, $todayUniqueVisits + 1);
            $this->uniqueVisitsRegistry->save();
        }

        $this->visitorsRegistry->set($ip, $date);
        $this->visitorsRegistry->save();

        $uri = $this->request->uri();
        $pageViews = $this->pageViewsRegistry->has($uri) ? (int) $this->pageViewsRegistry->get($uri) : 0;
        $this->pageViewsRegistry->set($uri, $pageViews + 1);
        $this->pageViewsRegistry->save();
    }

    /**
     * Return chart data
     *
     * @return array{labels: array<string>, series: list<list<int>>}
     */
    public function getChartData(int $limit = self::CHART_LIMIT): array
    {

        $visits = $this->getVisits($limit);
        $uniqueVisits = $this->getUniqueVisits($limit);

        $labels = Arr::map(
            iterator_to_array($this->generateDays($limit)),
            fn (string $day): string => Date::formatTimestamp(Date::toTimestamp($day, self::DATE_FORMAT), "D\nj M")
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
     * @return array<string, int>
     */
    public function getPageViews(): array
    {
        return Arr::sort($this->pageViewsRegistry->toArray(), SORT_DESC);
    }

    /**
     * @return array<string, int>
     */
    public function getVisits(int $limit = self::CHART_LIMIT): array
    {
        return $this->interpolateVisits($this->visitsRegistry->toArray(), $limit);
    }

    /**
     * @return array<string, int>
     */
    public function getUniqueVisits(int $limit = self::CHART_LIMIT): array
    {
        return $this->interpolateVisits($this->uniqueVisitsRegistry->toArray(), $limit);
    }

    /**
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
     * @return Generator<int, string>
     */
    private function generateDays(int $limit): Generator
    {
        $low = time() - ($limit - 1) * 86400;
        for ($i = 0; $i < $limit; $i++) {
            yield date(self::DATE_FORMAT, $low + $i * 86400);
        }
    }
}
