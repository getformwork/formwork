<?php

namespace Formwork\Controllers;

use Formwork\Formwork;
use Formwork\Pages\Page;
use Formwork\Response\FileResponse;
use Formwork\Response\RedirectResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;

class PageController extends AbstractController
{
    public function load(RouteParams $params): Response
    {
        $formwork = Formwork::instance();

        $route = $params->get('page', $formwork->config()->get('pages.index'));

        if ($resolvedAlias = $formwork->site()->resolveAlias($route)) {
            $route = $resolvedAlias;
        }

        if ($page = $formwork->site()->findPage($route)) {
            if ($page->canonical() !== null) {
                $canonical = $page->canonical();

                if ($params->get('page', '/') !== $canonical) {
                    $route = $formwork->router()->rewrite(['page' => $canonical]);
                    return new RedirectResponse($formwork->site()->uri($route), 301);
                }
            }

            if (($params->has('tagName') || $params->has('paginationPage')) && $page->scheme()->get('type') !== 'listing') {
                return $this->getPageResponse($formwork->site()->errorPage());
            }

            if ($formwork->config()->get('cache.enabled') && ($page->has('publish-date') || $page->has('unpublish-date'))) {
                if (($page->isPublished() && !$formwork->site()->modifiedSince($page->publishDate()->toTimestamp()))
                || (!$page->isPublished() && !$formwork->site()->modifiedSince($page->unpublishDate()->toTimestamp()))) {
                    // Clear cache if the site was not modified since the page has been published or unpublished
                    $formwork->cache()->clear();
                    FileSystem::touch($formwork->config()->get('content.path'));
                }
            }

            if ($page->isPublished() && $page->routable()) {
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

        $response = new Response($page->render(), $page->responseStatus(), $page->headers());

        if ($config->get('cache.enabled') && $page->cacheable()) {
            $cache->save($cacheKey, $response);
        }

        return $response;
    }
}
