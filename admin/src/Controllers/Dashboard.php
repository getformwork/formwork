<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Statistics;

class Dashboard extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index()
    {
        $this->ensurePermission('dashboard');

        $statistics = new Statistics();

        $this->modal('newPage', array(
            'templates' => $this->site()->templates(),
            'pages'     => $this->site()->descendants()->sort('path')
        ));

        $this->modal('deletePage');

        $this->view('admin', array(
            'title'   => $this->label('dashboard.dashboard'),
            'content' => $this->view('dashboard.index', array(
                'lastModifiedPages' => $this->view('pages.list', array(
                    'pages'    => $this->site()->descendants()->sort('lastModifiedTime', SORT_DESC)->slice(0, 5),
                    'subpages' => false,
                    'class'    => 'pages-list-top',
                    'parent'   => null,
                    'sortable' => false,
                    'headers'  => true
                    ), false),
                'statistics' => $statistics->getChartData()
            ), false)
        ));
    }
}
