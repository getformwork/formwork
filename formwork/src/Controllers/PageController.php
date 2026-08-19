<?php

namespace Formwork\Controllers;

use Formwork\Cache\AbstractCache;
use Formwork\Cms\Site;
use Formwork\Http\FileResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Events\PageOutputEvent;
use Formwork\Pages\Page;
use Formwork\Router\RouteParams;
use Formwork\Services\Attributes\Service;
use Formwork\Services\Container;
use Formwork\Statistics\Statistics;
use Formwork\Utils\FileSystem;

final class PageController extends AbstractController
{
    public function __construct(
        private Container $container,
        private Site $site,
        #[Service('cache.pages')]
        private AbstractCache $cache,
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * PageController@load action
     */
    public function load(RouteParams $routeParams, Statistics $statistics): Response
    {
        $trackable = $this->config->getBool('site.statistics.enabled');

        if ($this->isMaintenanceEnabled()) {
            $trackable = false;

            if (($maintenancePage = $this->site->get('maintenance.page')) instanceof Page) {
                $route = $maintenancePage->route();
            } else {
                $status = ResponseStatus::ServiceUnavailable;
                return new Response($this->view('@system.errors.maintenance', ['status' => $status->code(), 'message' => $status->message()]), $status);
            }
        }

        if (!isset($route)) {
            $route = $routeParams->get('page', $this->config->getString('system.pages.index'));

            if ($resolvedAlias = $this->site->resolveRouteAlias($route)) {
                $route = $resolvedAlias;
            }
        }

        if (($page = $this->site->findPage($route)) !== null) {
            if ($page->canonicalRoute() !== null) {
                $canonical = $page->canonicalRoute();

                if ($routeParams->get('page', '/') !== $canonical) {
                    $route = $this->router->rewrite(['page' => $canonical]);
                    return $this->redirect($route, ResponseStatus::MovedPermanently);
                }
            }

            if ($routeParams->has('paginationPage') && !$page->scheme()->options()->get('allowPagination', false)) {
                return $this->getPageResponse($this->site->errorPage());
            }

            if ($routeParams->has('taxonomy') && !$page->scheme()->options()->get('allowTaxonomy', false)) {
                return $this->getPageResponse($this->site->errorPage());
            }

            if ($this->shouldClearCacheForScheduledPublication($page)) {
                $this->cache->clear();
                if ($this->site->contentPath() !== null) {
                    FileSystem::touch($this->site->contentPath());
                }
            }

            if ($page->isPublished() && $page->routable()) {
                if ($trackable) {
                    $statistics->trackVisit();
                }
                return $this->getPageResponse($page);
            }
        } else {
            $filename = basename((string) $route);
            $upperLevel = dirname((string) $route);

            if ($upperLevel === '.') {
                $upperLevel = $this->config->getString('system.pages.index');
            }

            if (
                ($parent = $this->site->findPage($upperLevel)) !== null
                && ($file = $parent->files()->get($filename)) !== null
            ) {
                return new FileResponse($file->path(), autoEtag: true, autoLastModified: true);
            }
        }

        return $this->getPageResponse($this->site->errorPage());
    }

    /**
     * PageController@error action
     */
    public function error(): Response
    {
        return $this->getPageResponse($this->site->errorPage());
    }

    /**
     * Check if maintenance mode is enabled and the user is not logged in
     */
    private function isMaintenanceEnabled(): bool
    {
        return $this->site->get('maintenance.enabled') && !$this->app->panel()->isLoggedIn();
    }

    /**
     * Check if the cache should be cleared for a page with scheduled publication
     */
    private function shouldClearCacheForScheduledPublication(Page $page): bool
    {
        if (!$this->config->getBool('system.cache.enabled')) {
            return false;
        }

        if (!$page->fields()->has('publishDate') && !$page->fields()->has('unpublishDate')) {
            return false;
        }

        return ($page->isPublished() && !$page->publishDate()->isEmpty() && !$this->site->modifiedSince($page->publishDate()->toTimestamp()))
            || (!$page->isPublished() && !$page->unpublishDate()->isEmpty() && !$this->site->modifiedSince($page->unpublishDate()->toTimestamp()));
    }

    /**
     * Get the response for a page
     */
    private function getPageResponse(Page $page): Response
    {
        if ($this->site->currentPage() === null) {
            $this->site->setCurrentPage($page);
        }

        /**
         * @var Page
         */
        $page = $this->site->currentPage();

        // Use requested route as cache key to include parameters like pagination and tags
        $cacheKey = rawurlencode($this->router->request());

        $cacheable = $cacheKey !== '' && $this->isPageCacheable($page);

        if ($cacheable && ($cachedResponse = $this->getCachedResponse($cacheKey)) !== null) {
            return $cachedResponse;
        }

        $output = $page->render();
        $headers = [];

        if ($cacheable) {
            $lastModifiedTime = max($page->lastModifiedTime(), $this->site->lastModifiedTime());
            $headers = [
                'ETag'          => hash('sha256', "{$output}:{$lastModifiedTime}"),
                'Last-Modified' => gmdate('D, d M Y H:i:s T', $lastModifiedTime),
            ];
        }

        $this->events->dispatch(new PageOutputEvent($page, $output));

        $response = new Response($output, $page->responseStatus(), $page->headers() + $headers);

        if ($cacheable) {
            $this->cache->set($cacheKey, $response, $page->get('cache.time', null));
        }

        return $response;
    }

    /**
     * Return whether the page is cacheable
     */
    private function isPageCacheable(Page $page): bool
    {
        if (!$this->config->getBool('system.cache.enabled')) {
            return false;
        }

        if ($this->isMaintenanceEnabled()) {
            return false;
        }

        return $this->isRequestCacheable()
            && $page->cacheable()
            && !$page->isErrorPage();
    }

    /**
     * Return whether the request is cacheable
     */
    private function isRequestCacheable(): bool
    {
        return in_array($this->request->method(), [RequestMethod::GET, RequestMethod::HEAD])
            && $this->request->query()->isEmpty();
    }

    /**
     * Get a cached response for a given key, if it exists and is still valid
     *
     * @param non-empty-string $key
     */
    private function getCachedResponse(string $key): ?Response
    {
        $cacheItem = $this->cache->getItem($key);

        if ($cacheItem === null) {
            return null;
        }

        if ($this->site->modifiedSince($cacheItem->cachedTime())) {
            $this->cache->delete($key);
            return null;
        }

        $response = $cacheItem->value();

        if (!$response instanceof Response) {
            $this->cache->delete($key);
            return null;
        }

        return $response;
    }
}
