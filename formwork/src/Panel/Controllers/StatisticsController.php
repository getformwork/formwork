<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Parsers\Csv;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Statistics\Statistics;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use ZipArchive;

final class StatisticsController extends AbstractController
{
    /**
     * Statistics@index action
     */
    public function index(Statistics $statistics): Response
    {
        if (!$this->hasPermission('panel.statistics')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $pageViews = $statistics->getPageViews();
        $sources = $statistics->getSources();
        $devices = $statistics->getDevices();

        return new Response($this->view('@panel.statistics.index', [
            'title'             => $this->translate('panel.statistics.statistics'),
            'statistics'        => Json::encode($statistics->getChartData(31)),
            'pageViews'         => array_slice($pageViews, 0, 15, preserve_keys: true),
            'totalViews'        => array_sum($pageViews),
            'sources'           => array_slice($sources, 0, 15, preserve_keys: true),
            'totalSources'      => array_sum($sources),
            'devices'           => array_slice($devices, 0, 15, preserve_keys: true),
            'totalDevices'      => array_sum($devices),
            'monthVisits'       => array_sum($statistics->getVisits(31)),
            'weekVisits'        => array_sum($statistics->getVisits(7)),
            'monthUniqueVisits' => array_sum($statistics->getUniqueVisits(31)),
            'weekUniqueVisits'  => array_sum($statistics->getUniqueVisits(7)),
        ]));
    }

    /**
     * Statistics@download action
     */
    public function download(Statistics $statistics, RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.statistics.download')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $format = $routeParams->get('format', 'tsv');

        $chartData = $statistics->getChartData(31, 'Y-m-d');

        $stats = [
            'devices' => [
                ['Device', 'Count'],
                ...Arr::entries($statistics->getDevices()),
            ],
            'pageViews' => [
                ['Page', 'Count'],
                ...Arr::entries($statistics->getPageViews()),
            ],
            'sources' => [
                ['Source', 'Count'],
                ...Arr::entries($statistics->getSources()),
            ],
            'visits' => [
                ['Date', 'Visits', 'UniqueVisits'],
                ...Arr::zip([$chartData['labels'], ...$chartData['series']]),
            ],
        ];

        $file = FileSystem::joinPaths($this->config->getString('site.statistics.path'), sprintf('.export-%s.zip', FileSystem::randomName()));

        $zipArchive = new ZipArchive();
        $zipArchive->open($file, ZipArchive::CREATE);

        foreach ($stats as $name => $data) {
            $zipArchive->addFromString(
                sprintf('%s.%s', $name, $format),
                Csv::encode($data, ['separator' => $format === 'tsv' ? Csv::SEPARATOR_TAB : Csv::SEPARATOR_COMMA])
            );
        }

        $zipArchive->close();

        return (new FileResponse($file, download: true, deleteAfterSend: true))
            ->setFilename(sprintf('statistics-%s-%s.zip', Str::slug($this->site->title()), date('Ymd-His')));
    }
}
