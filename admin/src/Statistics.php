<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\IPAnonymizer;
use Formwork\Admin\Utils\Registry;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Visitor;

class Statistics
{
    /**
     * Date format
     *
     * @var string
     */
    protected const DATE_FORMAT = 'Ymd';

    /**
     * Number of days displayed in the statistics chart
     *
     * @var int
     */
    protected const CHART_LIMIT = 7;

    /**
     * Visits registry filename
     *
     * @var string
     */
    protected const VISITS_FILENAME = 'visits.json';

    /**
     * Unique visits registry filename
     *
     * @var string
     */
    protected const UNIQUE_VISITS_FILENAME = 'uniqueVisits.json';

    /**
     * Visitors registry filename
     *
     * @var string
     */
    protected const VISITORS_FILENAME = 'visitors.json';

    /**
     * Page views registry filename
     *
     * @var string
     */
    protected const PAGE_VIEWS_FILENAME = 'pageViews.json';

    /**
     * Visits registry
     *
     * @var Registry
     */
    protected $visitsRegistry;

    /**
     * Unique visits registry
     *
     * @var Registry
     */
    protected $uniqueVisitsRegistry;

    /**
     * Visitors registry
     *
     * @var Registry
     */
    protected $visitorsRegistry;

    /**
     * Page views registry
     *
     * @var Registry
     */
    protected $pageViewsRegistry;

    /**
     * Create a new Statistics instance
     */
    public function __construct()
    {
        $base = Admin::LOGS_PATH . 'statistics' . DS;

        if (!FileSystem::exists($base)) {
            FileSystem::createDirectory($base);
        }

        $this->visitsRegistry = new Registry($base . self::VISITS_FILENAME);
        $this->uniqueVisitsRegistry = new Registry($base . self::UNIQUE_VISITS_FILENAME);
        $this->visitorsRegistry = new Registry($base . self::VISITORS_FILENAME);
        $this->pageViewsRegistry = new Registry($base . self::PAGE_VIEWS_FILENAME);
    }

    /**
     * Track a visit
     */
    public function trackVisit()
    {
        if (Visitor::isBot() || !Visitor::isTrackable()) {
            return;
        }

        $date = date(self::DATE_FORMAT);
        $ip = IPAnonymizer::anonymize(HTTPRequest::ip());

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

        $uri = HTTPRequest::uri();
        $pageViews = $this->pageViewsRegistry->has($uri) ? (int) $this->pageViewsRegistry->get($uri) : 0;
        $this->pageViewsRegistry->set($uri, $pageViews + 1);
        $this->pageViewsRegistry->save();
    }

    /**
     * Return chart data
     *
     * @param int $limit
     *
     * @return array
     */
    public function getChartData($limit = self::CHART_LIMIT)
    {
        $visits = $this->visitsRegistry->toArray();
        $uniqueVisits = $this->uniqueVisitsRegistry->toArray();

        $limit = min($limit, count($visits), count($uniqueVisits));

        $low = time() - ($limit - 1) * 86400;

        $days = array();

        for ($i = 0; $i < $limit; $i++) {
            $value = date(self::DATE_FORMAT, $low + $i * 86400);
            $days[] = $value;
        }

        $visits = array_slice($visits, -$limit, null, true);
        $uniqueVisits = array_slice($uniqueVisits, -$limit, null, true);

        $label = static function ($day) {
            $time = strtotime($day);
            $month = Admin::instance()->label('date.months.short')[date('n', $time) - 1];
            $weekday = Admin::instance()->label('date.weekdays.short')[date('N', $time) % 7];
            $day = date('j', $time);
            return strtr("D\nj M", array('D' => $weekday, 'M' => $month, 'j' => $day));
        };

        $labels = array_map($label, $days);

        $interpolate = static function ($data) use ($days) {
            $output = array();
            foreach ($days as $day) {
                $output[$day] = $data[$day] ?? 0;
            }
            return $output;
        };

        return array(
            'labels' => $labels,
            'series' => array(
                array_values($interpolate($visits)),
                array_values($interpolate($uniqueVisits))
            )
        );
    }
}
