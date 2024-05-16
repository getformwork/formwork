<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\Response;
use Formwork\Parsers\Json;
use Formwork\Schemes\Schemes;
use Formwork\Statistics\Statistics;

class DashboardController extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(Schemes $schemes, Statistics $statistics): Response
    {
        $this->ensurePermission('dashboard');

        $this->modal('newPage', [
            'fields' => $schemes->get('modals.newPage')->fields(),
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
