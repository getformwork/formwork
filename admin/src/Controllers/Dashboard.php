<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Core\Formwork;

class Dashboard extends AbstractController
{
    public function index()
    {
        $site = Formwork::instance()->site();

        $statistics = new Statistics();

        $this->modal('newPage', array(
            'templates' => $site->templates(),
            'pages' => $site->descendants()->sort('path')
        ));

        $this->modal('deletePage');

        $this->view('admin', array(
            'title' => $this->label('dashboard.dashboard'),
            'content' => $this->view('dashboard.index', array(
                'user' => Admin::instance()->loggedUser(),
                'lastModifiedPages' => $this->view('pages.list', array(
                    'pages' => $site->descendants()->sort('lastModifiedTime', SORT_DESC)->slice(0, 5),
                    'subpages' => false,
                    'class' => 'pages-list-top',
                    'parent' => null,
                    'sortable' => 'false'
                    ), false),
                'statistics' => $statistics->getChartData()
            ), false)
        ));
    }
}
