<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\Response;
use Formwork\Parsers\Json;
use Formwork\Statistics\Statistics;

class DashboardController extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(Statistics $statistics): Response
    {
        $this->ensurePermission('dashboard');

        $this->modal('newPage', [
            'templates' => $this->site()->templates()->keys(),
            'pages'     => $this->site()->descendants()->sortBy('relativePath'),
        ]);

        $this->modal('deletePage');

        return new Response($this->view('dashboard.index', [
            'title'             => $this->translate('panel.dashboard.dashboard'),
            'lastModifiedPages' => $this->view('pages.tree', [
                'pages'           => $this->site()->descendants()->sortBy('lastModifiedTime', direction: SORT_DESC)->limit(5),
                'includeChildren' => false,
                'class'           => 'pages-tree-root',
                'parent'          => null,
                'orderable'       => false,
                'headers'         => true,
            ]),
            'statistics' => Json::encode($statistics->getChartData()),
        ]));
    }
}
