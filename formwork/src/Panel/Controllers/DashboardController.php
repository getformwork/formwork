<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\Response;
use Formwork\Parsers\Json;
use Formwork\Statistics\Statistics;

final class DashboardController extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(Statistics $statistics): Response
    {
        if (!$this->hasPermission('panel.dashboard')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $this->modal('newPage')->setFieldsModel($this->site);

        return new Response($this->view('dashboard.index', [
            'title'             => $this->translate('panel.dashboard.dashboard'),
            'lastModifiedPages' => $this->view('pages.tree', [
                'pages'           => $this->site->descendants()->sortBy('lastModifiedTime', direction: SORT_DESC)->limit(5),
                'includeChildren' => false,
                'orderable'       => false,
                'headers'         => true,
                'class'           => 'pages-tree-root',
            ]),
            'statistics' => Json::encode($statistics->getChartData()),
        ]));
    }
}
