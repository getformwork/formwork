<?php

namespace Formwork\Controllers;

use Formwork\Formwork;
use Formwork\Page;
use Formwork\Response\FileResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;

class PageController extends AbstractController
{
    public function load(RouteParams $params)
    {
        $config = Formwork::instance()->config();
        $router = Formwork::instance()->router();
        $cache = Formwork::instance()->cache();
        $site = Formwork::instance()->site();

        $route = $params->get('page', $config->get('pages.index'));

        if ($site->has('aliases') && $alias = $site->alias($route)) {
            $route = trim($alias, '/');
        }

        if ($page = $site->findPage($route)) {
            if ($page->has('canonical')) {
                $canonical = trim($page->canonical(), '/');
                if ($params->get('page', '') !== $canonical) {
                    $route = empty($canonical) ? '' : $router->rewrite(['page' => $canonical]);
                    Header::redirect($site->uri($route), 301);
                }
            }

            if (($params->has('tagName') || $params->has('paginationPage')) && $page->scheme()->get('type') !== 'listing') {
                return $site->errorPage();
            }

            if ($config->get('cache.enabled') && ($page->has('publish-date') || $page->has('unpublish-date'))) {
                if (($page->published() && !$site->modifiedSince(Date::toTimestamp($page->get('publish-date'))))
                || (!$page->published() && !$site->modifiedSince(Date::toTimestamp($page->get('unpublish-date'))))) {
                    // Clear cache if the site was not modified since the page has been published or unpublished
                    $cache->clear();
                    FileSystem::touch($config->get('content.path'));
                }
            }

            if ($page->routable() && $page->published()) {
                return $page;
            }
        } else {
            $filename = basename($route);
            $upperLevel = dirname($route);

            if ($upperLevel === '.') {
                $upperLevel = $config->get('pages.index');
            }

            if (($parent = $site->findPage($upperLevel)) && $parent->files()->has($filename)) {
                $response = new FileResponse($parent->files()->get($filename)->path());
                return $response->send();
            }
        }

        return $site->errorPage();
    }
}
