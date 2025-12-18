<?php

namespace Formwork\Controllers;

use Formwork\Cache\FilesCache;
use Formwork\Cms\Site;
use Formwork\Http\FileResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Page;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Statistics\Statistics;
use Formwork\Utils\FileSystem;

final class PageController extends AbstractController
{
    public function __construct(
        private Container $container,
        private Router $router,
        private Site $site,
        private FilesCache $filesCache,
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * PageController@load action
     */
    public function load(RouteParams $routeParams, Statistics $statistics): Response
    {
        $trackable = $this->config->get('site.statistics.enabled');

        if ($this->site->get('maintenance.enabled') && !$this->app->panel()->isLoggedIn()) {
            $trackable = false;

            if (($maintenancePage = $this->site->get('maintenance.page')) instanceof Page) {
                $route = $maintenancePage->route();
            } else {
                $status = ResponseStatus::ServiceUnavailable;
                return new Response($this->view('errors.maintenance', ['status' => $status->code(), 'message' => $status->message()]), $status);
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
                    return $this->redirect($route, ResponseStatus::MovedPermanently);
                }
            }

            if ($routeParams->has('paginationPage') && !$page->scheme()->options()->get('allowPagination', false)) {
                return $this->getPageResponse($this->site->errorPage());
            }

            if ($routeParams->has('taxonomy') && !$page->scheme()->options()->get('allowTaxonomy', false)) {
                return $this->getPageResponse($this->site->errorPage());
            }

            if ($this->config->get('system.cache.enabled') && ($page->fields()->has('publishDate') || $page->fields()->has('unpublishDate')) && (
                ($page->isPublished() && !$page->publishDate()->isEmpty() && !$this->site->modifiedSince($page->publishDate()->toTimestamp()))
                || (!$page->isPublished() && !$page->unpublishDate()->isEmpty() && !$this->site->modifiedSince($page->unpublishDate()->toTimestamp()))
            )) {
                // Clear cache if the site was not modified since the page has been published or unpublished
                $this->filesCache->clear();
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
                $upperLevel = $this->config->get('system.pages.index');
            }

            if ((($parent = $this->site->findPage($upperLevel)) !== null) && $parent->files()->has($filename)) {
                $file = $parent->files()->get($filename);
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
     * Get a response for a page
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
        $cacheKey = $this->router->request();

        $cacheable = $this->config->get('system.cache.enabled')
            && $this->isRequestCacheable()
            && $page->cacheable()
            && !$page->isErrorPage();

        if ($cacheable && $this->filesCache->has($cacheKey)) {
            /**
             * @var int
             */
            $cachedTime = $this->filesCache->cachedTime($cacheKey);
            // Validate cached response
            if (!$this->site->modifiedSince($cachedTime)) {
                return $this->filesCache->fetch($cacheKey);
            }
            $this->filesCache->delete($cacheKey);
        }

        $content = $page->render();
        $headers = [];

        if ($cacheable) {
            $lastModifiedTime = max($page->lastModifiedTime(), $this->site->lastModifiedTime());
            $headers = [
                'ETag'          => hash('sha256', $content . ':' . $lastModifiedTime),
                'Last-Modified' => gmdate('D, d M Y H:i:s T', $lastModifiedTime),
            ];
        }

        $response = new Response($content, $page->responseStatus(), $page->headers() + $headers);

        if ($cacheable) {
            $this->filesCache->save($cacheKey, $response, $page->get('cache.time', null));
        }

        return $response;
    }

    /**
     * Return whether the request is cacheable
     */
    private function isRequestCacheable(): bool
    {
        return in_array($this->request->method(), [RequestMethod::GET, RequestMethod::HEAD])
            && $this->request->query()->isEmpty();
    }
}
