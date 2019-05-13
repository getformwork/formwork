<?php

namespace Formwork\Core;

use Formwork\Utils\Header;
use Formwork\Utils\Uri;

class Pagination
{
    /**
     * Number of all items to paginate
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Number of items in each pagination page
     *
     * @var int
     */
    protected $length = 0;

    /**
     * Number of pagination pages
     *
     * @var int
     */
    protected $pages = 0;

    /**
     * Base URI to which append pagination page number
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Current pagination page
     *
     * @var int
     */
    protected $currentPage = 1;

    /**
     * Create a new Pagination instance
     *
     * @param int $count
     * @param int $length
     */
    public function __construct($count, $length)
    {
        $router = Formwork::instance()->router();

        $this->count = $count;
        $this->length = $length;
        $this->pages = $count > 0 ? (int) ceil($count / $length) : 1;

        $this->baseUri = Formwork::instance()->site()->uri(preg_replace('~/page/[0-9]+/?$~', '', $router->request()));

        $this->currentPage = (int) $router->params()->get('paginationPage', 1);

        if ($router->params()->get('paginationPage') == 1) {
            Header::redirect($this->baseUri, 301);
        }

        if ($this->currentPage > $this->pages || $this->currentPage < 1) {
            Formwork::instance()->site()->errorPage(true);
        }
    }

    /**
     * Get current page
     *
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get pagination length
     *
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * Get current pagination offset
     *
     * @return int
     */
    public function offset()
    {
        return ($this->currentPage - 1) * $this->length;
    }

    /**
     * Return whether a given page number exists
     *
     * @param int $number
     *
     * @return bool
     */
    public function hasPage($number)
    {
        return (int) $number > 1 && (int) $number <= $this->pages;
    }

    /**
     * Return whether current page is the first
     *
     * @return bool
     */
    public function firstPage()
    {
        return $this->currentPage === 1;
    }

    /**
     * Return whether current page is the last
     *
     * @return bool
     */
    public function lastPage()
    {
        return $this->currentPage === $this->pages;
    }

    /**
     * Return whether pagination has more than one page
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->pages > 1;
    }

    /**
     * Return whether a previous page exists
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        return !$this->firstPage();
    }

    /**
     * Return whether a next page exists
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !$this->lastPage();
    }

    /**
     * Get previous pagination page number
     *
     * @return bool|int
     */
    public function previousPage()
    {
        $previous = $this->currentPage - 1;
        return $previous > 0 ? $previous : false;
    }

    /**
     * Get next pagination page number
     *
     * @return bool|int
     */
    public function nextPage()
    {
        $next = $this->currentPage + 1;
        return $next > $this->pages ? false : $next;
    }

    /**
     * Get the URI of the next pagination page
     *
     * @return string
     */
    public function nextPageUri()
    {
        return Uri::make(array('path' => $this->baseUri . 'page/' . $this->nextPage()));
    }

    /**
     * Get the URI of the previous pagination page
     *
     * @return string
     */
    public function previousPageUri()
    {
        if ($this->previousPage() === 1) {
            return Uri::make(array('path' => $this->baseUri));
        }
        return Uri::make(array('path' => $this->baseUri . 'page/' . $this->previousPage()));
    }

    public function __debugInfo()
    {
        return array(
            'count'        => $this->count,
            'length'       => $this->length,
            'pages'        => $this->pages,
            'currentPage'  => $this->currentPage,
            'previousPage' => $this->previousPageUri()
        );
    }
}
