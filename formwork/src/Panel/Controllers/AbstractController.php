<?php

namespace Formwork\Panel\Controllers;

use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\ModalCollection;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Panel;
use Formwork\Parsers\Json;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Site;
use Formwork\Translations\Translations;
use Formwork\Users\User;
use Formwork\Utils\Date;
use Formwork\Utils\Uri;
use Stringable;

abstract class AbstractController extends BaseAbstractController
{
    protected ModalCollection $modals;

    public function __construct(
        private readonly Container $container,
        protected Router $router,
        protected CsrfToken $csrfToken,
        protected Translations $translations,
        protected ModalFactory $modalFactory,
        protected Site $site,
        protected Panel $panel
    ) {
        $this->container->call(parent::__construct(...));
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

    protected function redirect(string $route, ResponseStatus $responseStatus = ResponseStatus::Found): RedirectResponse
    {
        return new RedirectResponse($this->site->uri($route, includeLanguage: false), $responseStatus);
    }

    /**
     * Redirect to the referer page
     *
     * @param string $default Default route if HTTP referer is not available
     */
    protected function redirectToReferer(ResponseStatus $responseStatus = ResponseStatus::Found, string $default = '/'): RedirectResponse
    {
        if (!in_array($this->request->referer(), [null, Uri::current()], true) && $this->request->validateReferer($this->panel()->uri('/'))) {
            return new RedirectResponse($this->request->referer(), $responseStatus);
        }
        return new RedirectResponse($this->panel()->uri($default), $responseStatus);
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
            'location'   => $this->name,
            'site'       => $this->site(),
            'panel'      => $this->panel(),
            'csrfToken'  => $this->csrfToken->get($this->panel()->getCsrfTokenName()),
            'modals'     => $this->modals(),
            'navigation' => [
                'dashboard' => [
                    'label'       => $this->translate('panel.dashboard.dashboard'),
                    'uri'         => '/dashboard/',
                    'permissions' => 'dashboard',
                    'badge'       => null,
                ],
                'pages' => [
                    'label'       => $this->translate('panel.pages.pages'),
                    'uri'         => '/pages/',
                    'permissions' => 'pages',
                    'badge'       => $this->site->descendants()->count(),
                ],
                'statistics' => [
                    'label'       => $this->translate('panel.statistics.statistics'),
                    'uri'         => '/statistics/',
                    'permissions' => 'statistics',
                    'badge'       => null,
                ],
                'users' => [
                    'label'       => $this->translate('panel.users.users'),
                    'uri'         => '/users/',
                    'permissions' => 'users',
                    'badge'       => $this->site->users()->count(),
                ],
                'options' => [
                    'label'       => $this->translate('panel.options.options'),
                    'uri'         => '/options/',
                    'permissions' => 'options',
                    'badge'       => null,
                ],
                'tools' => [
                    'label'       => $this->translate('panel.tools.tools'),
                    'uri'         => '/tools/',
                    'permissions' => 'tools',
                    'badge'       => null,
                ],
                'logout' => [
                    'label'       => $this->translate('panel.login.logout'),
                    'uri'         => '/logout/',
                    'permissions' => '*',
                    'badge'       => null,
                ],
            ],
            'appConfig' => Json::encode([
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
                'EditorInput' => [
                    'labels' => [
                        'bold'           => $this->translate('panel.editor.bold'),
                        'italic'         => $this->translate('panel.editor.italic'),
                        'link'           => $this->translate('panel.editor.link'),
                        'image'          => $this->translate('panel.editor.image'),
                        'quote'          => $this->translate('panel.editor.quote'),
                        'undo'           => $this->translate('panel.editor.undo'),
                        'redo'           => $this->translate('panel.editor.redo'),
                        'bulletList'     => $this->translate('panel.editor.bulletList'),
                        'numberedList'   => $this->translate('panel.editor.numberedList'),
                        'code'           => $this->translate('panel.editor.code'),
                        'heading1'       => $this->translate('panel.editor.heading1'),
                        'heading2'       => $this->translate('panel.editor.heading2'),
                        'heading3'       => $this->translate('panel.editor.heading3'),
                        'heading4'       => $this->translate('panel.editor.heading4'),
                        'heading5'       => $this->translate('panel.editor.heading5'),
                        'heading6'       => $this->translate('panel.editor.heading6'),
                        'paragraph'      => $this->translate('panel.editor.paragraph'),
                        'increaseIndent' => $this->translate('panel.editor.increaseIndent'),
                        'decreaseIndent' => $this->translate('panel.editor.decreaseIndent'),
                    ],
                ],
                'SelectInput' => [
                    'labels' => [
                        'empty' => $this->translate(('fields.select.empty')),
                    ],
                ],
                'Backups' => [
                    'labels' => [
                        'now' => $this->translate('date.now'),
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
     * Get if current user has a permission
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->user()->permissions()->has($permission);
    }

    protected function modals(): ModalCollection
    {
        return $this->modals ??= new ModalCollection();
    }

    /**
     * Load a modal to be rendered later
     */
    protected function modal(string $name): Modal
    {
        $this->modals()->add($modal = $this->modalFactory->make($name));
        return $modal;
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
}
