<?php

namespace Formwork\Panel\Controllers;

use Formwork\Panel\Statistics;
use Formwork\Parsers\JSON;
use Formwork\Response\Response;

class DashboardController extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(): Response
    {
        $this->ensurePermission('dashboard');

        $statistics = new Statistics();

        $this->modal('newPage', [
            'templates' => $this->site()->templates()->keys(),
            'pages'     => $this->site()->descendants()->sortBy('relativePath')
        ]);

        $this->modal('deletePage');

        $timestamps = $this->site()->descendants()
            ->everyItem()->contentFile()
            ->everyItem()->lastModifiedTime();

        return new Response($this->view('dashboard.index', [
            'title'             => $this->translate('panel.dashboard.dashboard'),
            'lastModifiedPages' => $this->view('pages.list', [
                'pages'     => $this->site()->descendants()->sort(direction: SORT_DESC, sortBy: $timestamps->toArray())->limit(5),
                'subpages'  => false,
                'class'     => 'pages-list-root',
                'parent'    => null,
                'orderable' => false,
                'headers'   => true
                ], true),
            'statistics' => JSON::encode($statistics->getChartData())
        ], true));
    }
}
