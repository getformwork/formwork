<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Log;
use Formwork\Admin\Utils\Notification;
use Formwork\Admin\Utils\Registry;
use Formwork\Core\Page;
use Formwork\Core\Scheme;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
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
        return HTTPRequest::root() . ltrim($route, '/');
    }

    /**
     * Get the URI of the site
     *
     * @return string
     */
    protected function siteUri()
    {
        return rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/';
    }

    /**
     * Return the URI of a page
     *
     * @return string
     */
    protected function pageUri(Page $page)
    {
        return $this->siteUri() . ltrim($page->route(), '/');
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
        if (!is_null(HTTPRequest::referer()) && HTTPRequest::referer() !== Uri::current()) {
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
        return new Registry(LOGS_PATH . $name . '.json');
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
        return new Log(LOGS_PATH . $name . '.json');
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
        return Admin::instance()->language()->get(...$arguments);
    }
}
