<?php

namespace Formwork\Panel\Controllers;

use Formwork\Panel\Panel;
use Formwork\Panel\Security\CSRFToken;
use Formwork\Panel\Users\User;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Formwork;
use Formwork\Parsers\JSON;
use Formwork\Parsers\PHP;
use Formwork\Pages\Site;
use Formwork\Utils\Date;
use Formwork\Utils\HTTPRequest;
use Formwork\View\View;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * All loaded modals
     */
    protected array $modals = [];

    /**
     * Return panel instance
     */
    protected function panel(): Panel
    {
        return Formwork::instance()->panel();
    }

    /**
     * Return site instance
     */
    protected function site(): Site
    {
        return Formwork::instance()->site();
    }

    /*
     * Return default data passed to views
     *
     */
    protected function defaults(): array
    {
        return [
            'location'    => $this->name,
            'panel'       => $this->panel(),
            'csrfToken'   => CSRFToken::get(),
            'modals'      => implode('', $this->modals),
            'colorScheme' => $this->getColorScheme(),
            'appConfig'   => JSON::encode([
                'baseUri'   => $this->panel()->panelUri(),
                'DateInput' => [
                    'weekStarts' => Formwork::instance()->config()->get('date.week_starts'),
                    'format'     => Date::formatToPattern(Formwork::instance()->config()->get('date.format') . ' ' . Formwork::instance()->config()->get('date.time_format')),
                    'time'       => true,
                    'labels'     => [
                        'today'    => $this->panel()->translate('date.today'),
                        'weekdays' => ['long' => $this->panel()->translate('date.weekdays.long'), 'short' =>  $this->panel()->translate('date.weekdays.short')],
                        'months'   => ['long' => $this->panel()->translate('date.months.long'), 'short' =>  $this->panel()->translate('date.months.short')]
                    ]
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->panel()->translate('date.duration.years'),
                        'months'  => $this->panel()->translate('date.duration.months'),
                        'weeks'   => $this->panel()->translate('date.duration.weeks'),
                        'days'    => $this->panel()->translate('date.duration.days'),
                        'hours'   => $this->panel()->translate('date.duration.hours'),
                        'minutes' => $this->panel()->translate('date.duration.minutes'),
                        'seconds' => $this->panel()->translate('date.duration.seconds')
                    ]
                ]
            ])
        ];
    }

    /**
     * Get logged user
     */
    protected function user(): User
    {
        return $this->panel()->user();
    }

    /**
     * Ensure current user has a permission
     */
    protected function ensurePermission(string $permission): void
    {
        if (!$this->user()->permissions()->has($permission)) {
            $errors = new ErrorsController();
            $errors->forbidden()->send();
            exit;
        }
    }

    /**
     * Load a modal to be rendered later
     *
     * @param string $name Name of the modal
     * @param array  $data Data to pass to the modal
     */
    protected function modal(string $name, array $data = []): void
    {
        $this->modals[] = $this->view('modals.' . $name, $data, true);
    }

    /**
     * Render a view
     *
     * @param string $name   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return or render the view
     *
     * @return string|void
     */
    protected function view(string $name, array $data = [], bool $return = false)
    {
        $view = new View(
            $name,
            array_merge($this->defaults(), $data),
            Formwork::instance()->config()->get('views.paths.panel'),
            PHP::parseFile(PANEL_PATH . 'helpers.php')
        );
        return $view->render($return);
    }

    /**
     * Get color scheme
     */
    private function getColorScheme(): string
    {
        $default = Formwork::instance()->config()->get('panel.color_scheme');
        if ($this->panel()->isLoggedIn()) {
            if ($this->user()->colorScheme() === 'auto') {
                return HTTPRequest::cookies()->get('formwork_preferred_color_scheme', $default);
            }
            return $this->user()->colorScheme();
        }
        return $default;
    }
}
