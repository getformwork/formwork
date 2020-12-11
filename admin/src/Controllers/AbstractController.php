<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\User;
use Formwork\Admin\Utils\DateFormats;
use Formwork\Admin\View\View;
use Formwork\Core\Formwork;
use Formwork\Core\Site;
use Formwork\Parsers\JSON;
use Formwork\Utils\Str;

abstract class AbstractController
{
    use AdminTrait;

    /**
     * Current panel location
     *
     * @var string
     */
    protected $location;

    /**
     * All loaded modals
     *
     * @var array
     */
    protected $modals = [];

    /**
     * Create a new Controller instance
     */
    public function __construct()
    {
        $this->location = strtolower(Str::afterLast(static::class, '\\'));
    }

    /**
     * Return site instance
     */
    protected function site(): Site
    {
        return Formwork::instance()->site();
    }

    /**
     * Get a system option
     */
    protected function option(string $option, $default = null)
    {
        return Formwork::instance()->option($option, $default);
    }

    /*
     * Return default data passed to views
     *
     */
    protected function defaults(): array
    {
        return [
            'location'    => $this->location,
            'csrfToken'   => CSRFToken::get(),
            'modals'      => implode($this->modals),
            'colorScheme' => $this->getColorScheme(),
            'appConfig'   => JSON::encode([
                'baseUri'    => $this->panelUri(),
                'DatePicker' => [
                    'weekStarts' => $this->option('date.week_starts'),
                    'format'     => DateFormats::formatToPattern(Formwork::instance()->option('date.format')),
                    'labels'     => [
                        'today'    => $this->label('date.today'),
                        'weekdays' => ['long' => $this->label('date.weekdays.long'), 'short' =>  $this->label('date.weekdays.short')],
                        'months'   => ['long' => $this->label('date.months.long'), 'short' =>  $this->label('date.months.short')]
                    ]
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->label('date.duration.years'),
                        'months'  => $this->label('date.duration.months'),
                        'weeks'   => $this->label('date.duration.weeks'),
                        'days'    => $this->label('date.duration.days'),
                        'hours'   => $this->label('date.duration.hours'),
                        'minutes' => $this->label('date.duration.minutes'),
                        'seconds' => $this->label('date.duration.seconds')
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
        return Admin::instance()->user();
    }

    /**
     * Ensure current user has a permission
     */
    protected function ensurePermission(string $permission): void
    {
        if (!$this->user()->permissions()->has($permission)) {
            $errors = new Errors();
            $errors->forbidden();
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
        $view = new View($name, array_merge($this->defaults(), $data));
        return $view->render($return);
    }

    /**
     * Get color scheme
     */
    private function getColorScheme(): string
    {
        $default = $this->option('admin.color_scheme');
        if (Admin::instance()->isLoggedIn()) {
            if ($this->user()->colorScheme() === 'auto') {
                return $_COOKIE['formwork_preferred_color_scheme'] ?? $default;
            }
            return $this->user()->colorScheme();
        }
        return $default;
    }
}
