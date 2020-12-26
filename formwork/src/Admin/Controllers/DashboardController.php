<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Statistics;
use Formwork\Parsers\JSON;

class DashboardController extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(): void
    {
        $this->ensurePermission('dashboard');

        $statistics = new Statistics();

        $this->modal('newPage', [
            'templates' => $this->site()->templates(),
            'pages'     => $this->site()->descendants()->sort('path')
        ]);

        $this->modal('deletePage');

        $this->view('dashboard.index', [
            'title'             => $this->admin()->translate('admin.dashboard.dashboard'),
            'lastModifiedPages' => $this->view('pages.list', [
                'pages'    => $this->site()->descendants()->sort('lastModifiedTime', SORT_DESC)->slice(0, 5),
                'subpages' => false,
                'class'    => 'pages-list-top',
                'parent'   => null,
                'sortable' => false,
                'headers'  => true
                ], true),
            'statistics' => JSON::encode($statistics->getChartData())
        ]);
    }
}
