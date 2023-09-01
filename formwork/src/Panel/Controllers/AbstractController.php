<?php

namespace Formwork\Panel\Controllers;

use Formwork\App;
use Formwork\Config;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Site;
use Formwork\Panel\Panel;
use Formwork\Panel\Users\User;
use Formwork\Parsers\Json;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\Date;
use Formwork\Utils\Uri;
use Formwork\View\ViewFactory;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * All loaded modals
     */
    protected array $modals = [];

    public function __construct(
        private Container $container,
        protected App $app,
        protected Config $config,
        protected ViewFactory $viewFactory,
        protected Request $request,
        protected Router $router,
        protected CsrfToken $csrfToken,
        protected Translations $translations,
        protected Site $site,
        protected Panel $panel
    ) {
        parent::__construct();
    }

    /**
     * Return panel instance
     */
    protected function panel(): Panel
    {
        return $this->panel;
    }

    /**
     * Return site instance
     */
    protected function site(): Site
    {
        return $this->site;
    }

    protected function generateRoute(string $name, array $params = [])
    {
        return $this->router->generate($name, $params);
    }

    protected function redirect(string $route, ResponseStatus $status = ResponseStatus::Found): RedirectResponse
    {
        return new RedirectResponse($this->site->uri($route, includeLanguage: false), $status);
    }

    /**
     * Redirect to the referer page
     *
     * @param string $default Default route if HTTP referer is not available
     */
    protected function redirectToReferer(ResponseStatus $status = ResponseStatus::Found, string $default = '/'): RedirectResponse
    {
        if ($this->request->validateReferer($this->panel()->uri('/')) && $this->request->referer() !== Uri::current()) {
            return new RedirectResponse($this->request->referer(), $status);
        }
        return new RedirectResponse($this->panel()->uri($default), $status);
    }

    protected function translate(...$arguments)
    {
        return $this->translations->getCurrent()->translate(...$arguments);
    }

    /*
     * Return default data passed to views
     *
     */
    protected function defaults(): array
    {
        return [
            'location'    => $this->name,
            'site'        => $this->site(),
            'panel'       => $this->panel(),
            'csrfToken'   => $this->csrfToken->get(),
            'modals'      => implode('', $this->modals),
            'colorScheme' => $this->getColorScheme(),
            'appConfig'   => Json::encode([
                'baseUri'   => $this->panel()->panelUri(),
                'DateInput' => [
                    'weekStarts' => $this->config->get('system.date.weekStarts'),
                    'format'     => Date::formatToPattern($this->config->get('system.date.datetimeFormat')),
                    'time'       => true,
                    'labels'     => [
                        'today'    => $this->translate('date.today'),
                        'weekdays' => ['long' => $this->translate('date.weekdays.long'), 'short' => $this->translate('date.weekdays.short')],
                        'months'   => ['long' => $this->translate('date.months.long'), 'short' => $this->translate('date.months.short')],
                    ],
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->translate('date.duration.years'),
                        'months'  => $this->translate('date.duration.months'),
                        'weeks'   => $this->translate('date.duration.weeks'),
                        'days'    => $this->translate('date.duration.days'),
                        'hours'   => $this->translate('date.duration.hours'),
                        'minutes' => $this->translate('date.duration.minutes'),
                        'seconds' => $this->translate('date.duration.seconds'),
                    ],
                ],
                'SelectInput' => [
                    'labels' => [
                        'empty' => $this->translate(('fields.select.empty')),
                    ],
                ],
            ]),
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
            $this->container->build(ErrorsController::class)
                ->forbidden()
                ->send();
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
        $this->modals[] = $this->view('modals.' . $name, $data, return: true);
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
        $view = $this->viewFactory->make(
            $name,
            [...$this->defaults(), ...$data],
            $this->config->get('system.views.paths.panel'),
        );
        return $view->render($return);
    }

    /**
     * Get color scheme
     */
    private function getColorScheme(): string
    {
        $default = $this->config->get('system.panel.colorScheme');
        if ($this->panel()->isLoggedIn()) {
            if ($this->user()->colorScheme() === 'auto') {
                return $this->request->cookies()->get('formwork_preferred_color_scheme', $default);
            }
            return $this->user()->colorScheme();
        }
        return $default;
    }
}
