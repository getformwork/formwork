<?php

namespace Formwork\Panel\Controllers;

use Formwork\App;
use Formwork\Config\Config;
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
use Stringable;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * All loaded modals
     *
     * @var array<string>
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

    /**
     * @param array<string, mixed> $params
     */
    protected function generateRoute(string $name, array $params = []): string
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
        if (!in_array($this->request->referer(), [null, Uri::current()], true) && $this->request->validateReferer($this->panel()->uri('/'))) {
            return new RedirectResponse($this->request->referer(), $status);
        }
        return new RedirectResponse($this->panel()->uri($default), $status);
    }

    protected function translate(string $key, int|float|string|Stringable ...$arguments): string
    {
        return $this->translations->getCurrent()->translate($key, ...$arguments);
    }

    /**
     * Return default data passed to views
     *
     * @return array<string, mixed>
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
                        'weekdays' => ['long' => $this->translations->getCurrent()->getStrings('date.weekdays.long'), 'short' => $this->translations->getCurrent()->getStrings('date.weekdays.short')],
                        'months'   => ['long' => $this->translations->getCurrent()->getStrings('date.months.long'), 'short' => $this->translations->getCurrent()->getStrings('date.months.short')],
                    ],
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->translations->getCurrent()->getStrings('date.duration.years'),
                        'months'  => $this->translations->getCurrent()->getStrings('date.duration.months'),
                        'weeks'   => $this->translations->getCurrent()->getStrings('date.duration.weeks'),
                        'days'    => $this->translations->getCurrent()->getStrings('date.duration.days'),
                        'hours'   => $this->translations->getCurrent()->getStrings('date.duration.hours'),
                        'minutes' => $this->translations->getCurrent()->getStrings('date.duration.minutes'),
                        'seconds' => $this->translations->getCurrent()->getStrings('date.duration.seconds'),
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
     * @param array<string, mixed> $data
     */
    protected function modal(string $name, array $data = []): void
    {
        $this->modals[] = $this->view('modals.' . $name, $data);
    }

    /**
     * Render a view
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): string
    {
        $view = $this->viewFactory->make(
            $name,
            [...$this->defaults(), ...$data],
            $this->config->get('system.views.paths.panel'),
        );
        return $view->render();
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
