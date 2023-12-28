<?php

namespace Formwork\Controllers;

use Formwork\App;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Page;
use Formwork\Pages\Site;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;

class PageController extends AbstractController
{
    public function __construct(protected App $app, protected Config $config, protected Router $router, protected Site $site, protected FilesCache $filesCache)
    {
        parent::__construct();
    }

    public function load(RouteParams $routeParams, ViewFactory $viewFactory): Response
    {
        if ($this->site->get('maintenance.enabled') && !$this->app->panel()?->isLoggedIn()) {
            if ($this->site->get('maintenance.page') !== null) {
                $route = $this->site->get('maintenance.page')->route();
            } else {
                $status = ResponseStatus::ServiceUnavailable;
                $view = $viewFactory->make('errors.maintenance', ['status' => $status->code(), 'message' => $status->message()]);
                return new Response($view->render(), $status);
            }
        }

        if (!isset($route)) {
            $route = $routeParams->get('page', $this->config->get('system.pages.index'));

            if ($resolvedAlias = $this->site->resolveRouteAlias($route)) {
                $route = $resolvedAlias;
            }
        }

        if (($page = $this->site->findPage($route)) !== null) {
            if ($page->canonicalRoute() !== null) {
                $canonical = $page->canonicalRoute();

                if ($routeParams->get('page', '/') !== $canonical) {
                    $route = $this->router->rewrite(['page' => $canonical]);
                    return new RedirectResponse($this->site->uri($route), ResponseStatus::MovedPermanently);
                }
            }

            if (($routeParams->has('tagName') || $routeParams->has('paginationPage')) && $page->scheme()->options()->get('type') !== 'listing') {
                return $this->getPageResponse($this->site->errorPage());
            }

            if ($this->config->get('system.cache.enabled') && ($page->has('publishDate') || $page->has('unpublishDate')) && (
                ($page->isPublished() && !$page->publishDate()->isEmpty() && !$this->site->modifiedSince($page->publishDate()->toTimestamp()))
                || (!$page->isPublished() && !$page->unpublishDate()->isEmpty() && !$this->site->modifiedSince($page->unpublishDate()->toTimestamp()))
            )) {
                // Clear cache if the site was not modified since the page has been published or unpublished
                $this->filesCache->clear();
                if ($this->site->path() !== null) {
                    FileSystem::touch($this->site->path());
                }
            }

            if ($page->isPublished() && $page->routable()) {
                return $this->getPageResponse($page);
            }
        } else {
            $filename = basename($route);
            $upperLevel = dirname($route);

            if ($upperLevel === '.') {
                $upperLevel = $this->config->get('system.pages.index');
            }

            if ((($parent = $this->site->findPage($upperLevel)) !== null) && $parent->files()->has($filename)) {
                return new FileResponse($parent->files()->get($filename)->path());
            }
        }

        return $this->getPageResponse($this->site->errorPage());
    }

    protected function getPageResponse(Page $page): Response
    {
        $site = $this->site;

        if ($site->currentPage() === null) {
            $site->setCurrentPage($page);
        }

        /**
         * @var Page
         */
        $page = $site->currentPage();

        $config = $this->config;

        $cacheKey = $page->uri(includeLanguage: true);

        if ($config->get('system.cache.enabled') && $this->filesCache->has($cacheKey)) {
            /**
             * @var int
             */
            $cachedTime = $this->filesCache->cachedTime($cacheKey);
            // Validate cached response
            if (!$site->modifiedSince($cachedTime)) {
                return $this->filesCache->fetch($cacheKey);
            }

            $this->filesCache->delete($cacheKey);
        }

        $response = new Response($page->render(), $page->responseStatus(), $page->headers());

        if ($config->get('system.cache.enabled') && $page->cacheable()) {
            $this->filesCache->save($cacheKey, $response);
        }

        return $response;
    }
}
