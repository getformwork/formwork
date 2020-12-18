<?php

namespace Formwork\Admin;

use Formwork\Utils\Log;
use Formwork\Utils\Notification;
use Formwork\Utils\Registry;
use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

trait AdminTrait
{
    /**
     * Return a URI relative to the request root
     */
    protected function uri(string $route): string
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return a URI relative to the real Admin root
     */
    protected function realUri(string $route): string
    {
        return HTTPRequest::root() . 'admin/' . ltrim($route, '/');
    }

    /**
     * Get the URI of the site
     */
    protected function siteUri(): string
    {
        return HTTPRequest::root();
    }

    /**
     * Return panel root
     */
    protected function panelRoot(): string
    {
        return Uri::normalize(Formwork::instance()->config()->get('admin.root'));
    }

    /**
     * Get the URI of the panel
     */
    protected function panelUri(): string
    {
        return HTTPRequest::root() . ltrim($this->panelRoot(), '/');
    }

    /**
     * Return the URI of a page
     *
     * @param bool|string $includeLanguage
     */
    protected function pageUri(Page $page, $includeLanguage = true): string
    {
        $base = $this->siteUri();
        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : $page->language();
            if ($language !== null) {
                $base .= $language . '/';
            }
        }
        return $base . ltrim($page->route(), '/');
    }

    /**
     * Return current route
     */
    protected function route(): string
    {
        return '/' . Str::removeStart(HTTPRequest::uri(), $this->panelRoot());
    }

    /**
     * Redirect to a given route
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirect(string $route, int $code = 302): void
    {
        Header::redirect($this->uri($route), $code);
    }

    /**
     * Redirect to the site index page
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToSite(int $code = 302): void
    {
        Header::redirect($this->siteUri(), $code);
    }

    /**
     * Redirect to the administration panel
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToPanel(int $code = 302): void
    {
        $this->redirect('/', $code);
    }

    /**
     * Redirect to the referer page
     *
     * @param int    $code    HTTP redirect status code
     * @param string $default Default route if HTTP referer is not available
     */
    protected function redirectToReferer(int $code = 302, string $default = '/'): void
    {
        if (HTTPRequest::validateReferer($this->uri('/')) && HTTPRequest::referer() !== Uri::current()) {
            Header::redirect(HTTPRequest::referer(), $code);
        } else {
            Header::redirect($this->uri($default), $code);
        }
    }

    /**
     * Get scheme object from template name
     */
    protected function scheme(string $template): Scheme
    {
        return new Scheme(Formwork::instance()->config()->get('templates.path') . 'schemes' . DS . $template . '.yml');
    }

    /**
     * Get a Registry object by name from logs path
     */
    protected function registry(string $name): Registry
    {
        return new Registry(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Get a Log object by name from logs path
     */
    protected function log(string $name): Log
    {
        return new Log(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Send a notification
     */
    protected function notify(string $text, string $type = Notification::INFO): void
    {
        Notification::send($text, $type);
    }

    /**
     * Get notification from session data
     */
    protected function notification(): ?array
    {
        return Notification::exists() ? Notification::get() : null;
    }

    /**
     * Get a translation
     */
    protected function translate(...$arguments)
    {
        return Admin::instance()->translation()->translate(...$arguments);
    }
}
