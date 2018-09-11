<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Core\Formwork;

class Dashboard extends AbstractController
{
    public function index()
    {
        $site = Formwork::instance()->site();
        $csrfToken = CSRFToken::get();

        $statistics = new Statistics();

        $modals[] = $this->view(
            'modals.newPage',
            array(
                'templates' => $site->templates(),
                'pages' => $site->descendants()->sort('path'),
                'csrfToken' => $csrfToken
            ),
            false
        );

        $modals[] = $this->view(
            'modals.deletePage',
            array('csrfToken' => $csrfToken),
            false
        );

        $data = array(
            'user' => Admin::instance()->loggedUser(),
            'lastModifiedPages' => $this->view(
                'pages.list',
                array(
                    'pages' => $site->descendants()->sort('lastModifiedTime', SORT_DESC)->slice(0, 5),
                    'subpages' => false,
                    'class' => 'pages-list-top',
                    'parent' => null,
                    'sortable' => 'false'
                ),
                false
            ),
            'statistics' => $statistics->getChartData(),
            'csrfToken' => $csrfToken
        );

        $this->view('admin', array(
            'title' => $this->label('dashboard.dashboard'),
            'location' => 'dashboard',
            'content' => $this->view('dashboard.index', $data, false),
            'modals' => implode($modals),
            'csrfToken' => $csrfToken
        ));
    }
}
