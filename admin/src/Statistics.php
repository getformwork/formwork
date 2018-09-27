<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\IPAnonymizer;
use Formwork\Admin\Utils\Registry;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Visitor;

class Statistics
{
    const DATE_FORMAT = 'Ymd';

    const CHART_LIMIT = 7;

    const VISITS_FILENAME = 'visits.json';

    const UNIQUE_VISITS_FILENAME = 'uniqueVisits.json';

    const VISITORS_FILENAME = 'visitors.json';

    protected $visitsRegistry;

    protected $uniqueVisitsRegistry;

    protected $visitorsRegistry;

    public function __construct()
    {
        $base = LOGS_PATH . 'statistics' . DS;

        if (!FileSystem::exists($base)) {
            FileSystem::createDirectory($base);
        }

        $this->visitsRegistry = new Registry($base . self::VISITS_FILENAME);
        $this->uniqueVisitsRegistry = new Registry($base . self::UNIQUE_VISITS_FILENAME);
        $this->visitorsRegistry = new Registry($base . self::VISITORS_FILENAME);
    }

    public function trackVisit()
    {
        if (Visitor::isBot()) {
            return;
        }

        $date = date(static::DATE_FORMAT);
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
    }

    public function getChartData()
    {
        $visits = $this->visitsRegistry->toArray();
        $uniqueVisits = $this->uniqueVisitsRegistry->toArray();

        $limit = min(self::CHART_LIMIT, count($visits), count($uniqueVisits));

        $low = time() - ($limit - 1) * 86400;

        $days = array();

        for ($i = 0; $i < $limit; $i++) {
            $value = date(self::DATE_FORMAT, $low + $i * 86400);
            $days[] = $value;
        }

        $visits = array_slice($visits, -$limit, null, true);
        $uniqueVisits = array_slice($uniqueVisits, -$limit, null, true);

        $label = function ($day) {
            $time = strtotime($day);
            $month = Admin::instance()->label('date.months.short')[date('n', $time) - 1];
            $weekday = Admin::instance()->label('date.weekdays.short')[date('N', $time) % 7];
            $day = date('j', $time);
            return strtr("D\nj M", array('D' => $weekday, 'M' => $month, 'j' => $day));
        };

        $labels = array_map($label, $days);

        $interpolate = function ($data) use ($days) {
            $output = array();
            foreach ($days as $day) {
                $output[$day] = isset($data[$day]) ? $data[$day] : 0;
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
