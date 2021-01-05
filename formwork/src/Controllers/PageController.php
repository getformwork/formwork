<?php

namespace Formwork\Controllers;

use Formwork\Formwork;
use Formwork\Page;
use Formwork\Response\FileResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;

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
                    Header::redirect($formwork->site()->uri($route), 301);
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

        if ($formwork->site()->currentPage() === null) {
            $formwork->site()->setCurrentPage($page);
        }

        $page = $formwork->site()->currentPage();

        if ($formwork->config()->get('cache.enabled') && $formwork->cache()->has($formwork->request())) {
            return $formwork->cache()->fetch($formwork->request());
        }

        $response = new Response($page->renderToString(), (int) $page->get('response_status', 200), $page->headers());

        if ($formwork->config()->get('cache.enabled') && $page->cacheable()) {
            $formwork->cache()->save($formwork->request(), $response);
        }

        return $response;
    }
}
