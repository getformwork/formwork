<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Log;
use Formwork\Admin\Utils\Notification;
use Formwork\Admin\Utils\Registry;
use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Template\Scheme;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

trait AdminTrait
{
    /**
     * Return a URI relative to the request root
     *
     * @param string $route
     *
     * @return string
     */
    protected function uri($route)
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return a URI relative to the real Admin root
     *
     * @param string $route
     *
     * @return string
     */
    protected function realUri($route)
    {
        return HTTPRequest::root() . 'admin/' . ltrim($route, '/');
    }

    /**
     * Get the URI of the site
     *
     * @return string
     */
    protected function siteUri()
    {
        return HTTPRequest::root();
    }

    /**
     * Return panel root
     *
     * @return string
     */
    protected function panelRoot()
    {
        return Uri::normalize(Formwork::instance()->option('admin.root'));
    }

    /**
     * Get the URI of the panel
     *
     * @return string
     */
    protected function panelUri()
    {
        return HTTPRequest::root() . ltrim($this->panelRoot(), '/');
    }

    /**
     * Return the URI of a page
     *
     * @param Page        $page
     * @param bool|string $includeLanguage
     *
     * @return string
     */
    protected function pageUri(Page $page, $includeLanguage = true)
    {
        $base = $this->siteUri();
        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : $page->language();
            if (!is_null($language)) {
                $base .= $language . '/';
            }
        }
        return $base . ltrim($page->route(), '/');
    }

    /**
     * Return current route
     *
     * @return string
     */
    protected function route()
    {
        return '/' . Str::removeStart(HTTPRequest::uri(), $this->panelRoot());
    }

    /**
     * Redirect to a given route
     *
     * @param string $route
     * @param int    $code  HTTP redirect status code
     */
    protected function redirect($route, $code = 302)
    {
        Header::redirect($this->uri($route), $code);
    }

    /**
     * Redirect to the site index page
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToSite($code = 302)
    {
        Header::redirect($this->siteUri(), $code);
    }

    /**
     * Redirect to the administration panel
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToPanel($code = 302)
    {
        $this->redirect('/', $code);
    }

    /**
     * Redirect to the referer page
     *
     * @param int    $code    HTTP redirect status code
     * @param string $default Default route if HTTP referer is not available
     */
    protected function redirectToReferer($code = 302, $default = '/')
    {
        if (HTTPRequest::validateReferer($this->uri('/')) && HTTPRequest::referer() !== Uri::current()) {
            Header::redirect(HTTPRequest::referer(), $code);
        } else {
            Header::redirect($this->uri($default), $code);
        }
    }

    /**
     * Get scheme object from template name
     *
     * @param string $template
     *
     * @return Scheme
     */
    protected function scheme($template)
    {
        return new Scheme($template);
    }

    /**
     * Get a Registry object by name from logs path
     *
     * @param string $name
     *
     * @return Registry
     */
    protected function registry($name)
    {
        return new Registry(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Get a Log object by name from logs path
     *
     * @param string $name
     *
     * @return Log
     */
    protected function log($name)
    {
        return new Log(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Send a notification
     *
     * @param string $text
     * @param string $type Notification type ('error', 'info', 'success', 'warning')
     */
    protected function notify($text, $type)
    {
        Notification::send($text, $type);
    }

    /**
     * Get notification from session data
     *
     * @return array|null
     */
    protected function notification()
    {
        return Notification::exists() ? Notification::get() : null;
    }

    /**
     * Get a label from language string
     *
     * @param float|int|string ...$arguments
     *
     * @return string
     */
    protected function label(...$arguments)
    {
        return Admin::instance()->translation()->get(...$arguments);
    }
}
