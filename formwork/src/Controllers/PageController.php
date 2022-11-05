<?php

namespace Formwork\Controllers;

use Formwork\Formwork;
use Formwork\Page;
use Formwork\Response\FileResponse;
use Formwork\Response\RedirectResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;

class PageController extends AbstractController
{
    public function load(RouteParams $params): Response
    {
        $formwork = Formwork::instance();

        $route = $params->get('page', $formwork->config()->get('pages.index'));

        if ($formwork->site()->has('aliases') && $alias = $formwork->site()->alias($route)) {
            $route = trim($alias, '/');
        }

        if ($page = $formwork->site()->findPage($route)) {
            if ($page->has('canonical')) {
                $canonical = trim($page->canonical(), '/');
                if ($params->get('page', '') !== $canonical) {
                    $route = empty($canonical) ? '' : $formwork->router()->rewrite(['page' => $canonical]);
                    return new RedirectResponse($formwork->site()->uri($route), 301);
                }
            }

            if (($params->has('tagName') || $params->has('paginationPage')) && $page->scheme()->get('type') !== 'listing') {
                return $this->getPageResponse($formwork->site()->errorPage());
            }

            if ($formwork->config()->get('cache.enabled') && ($page->has('publish-date') || $page->has('unpublish-date'))) {
                if (($page->published() && !$formwork->site()->modifiedSince(Date::toTimestamp($page->get('publish-date'))))
                || (!$page->published() && !$formwork->site()->modifiedSince(Date::toTimestamp($page->get('unpublish-date'))))) {
                    // Clear cache if the site was not modified since the page has been published or unpublished
                    $formwork->cache()->clear();
                    FileSystem::touch($formwork->config()->get('content.path'));
                }
            }

            if ($page->routable() && $page->published()) {
                return $this->getPageResponse($page);
            }
        } else {
            $filename = basename($route);
            $upperLevel = dirname($route);

            if ($upperLevel === '.') {
                $upperLevel = $formwork->config()->get('pages.index');
            }

            if (($parent = $formwork->site()->findPage($upperLevel)) && $parent->files()->has($filename)) {
                return new FileResponse($parent->files()->get($filename)->path());
            }
        }

        return $this->getPageResponse($formwork->site()->errorPage());
    }

    protected function getPageResponse(Page $page): Response
    {
        $formwork = Formwork::instance();

        $site = $formwork->site();

        if ($site->currentPage() === null) {
            $site->setCurrentPage($page);
        }

        $page = $site->currentPage();

        $config = $formwork->config();

        $cache = $formwork->cache();

        $cacheKey = $page->route();

        if ($config->get('cache.enabled') && $cache->has($cacheKey)) {
            // Validate cached response
            if (!$site->modifiedSince($cache->cachedTime($cacheKey))) {
                return $cache->fetch($cacheKey);
            }

            $cache->delete($cacheKey);
        }

        $response = new Response($page->renderToString(), (int) $page->get('response_status', 200), $page->headers());

        if ($config->get('cache.enabled') && $page->cacheable()) {
            $cache->save($cacheKey, $response);
        }

        return $response;
    }
}
