<?php

namespace Formwork\Core;
use Formwork\Utils\Uri;
use Formwork\Utils\HTTPRequest;

class Pagination {

    protected $count = 0;

    protected $length = 0;

    protected $pages = 0;

    protected $baseUri;

    protected $currentPage = 1;

    public function __construct($count, $length) {
        $this->count = $count;
        $this->length = $length;
        $this->pages = $count > 0 ? (int) ceil($count / $length) : 1;
        $this->baseUri = Uri::normalize(HTTPRequest::root() . Formwork::instance()->router()->params()->get('page'));
        $this->currentPage = (int) Formwork::instance()->router()->params()->get('paginationPage', 1);
        if ($this->currentPage > $this->pages || $this->currentPage < 1) {
            Formwork::instance()->site()->errorPage(true);
        }
    }

    public function currentPage() {
        return $this->currentPage;
    }

    public function length() {
        return $this->length;
    }

    public function offset() {
        return ($this->currentPage - 1) * $this->length;
    }

    public function hasPage($number) {
        return ((int) $number > 1 && (int) $number <= $this->pages);
    }

    public function firstPage() {
        return ($this->currentPage == 1);
    }

    public function lastPage() {
        return ($this->currentPage == $this->pages);
    }

    public function hasPages() {
        return $this->pages > 1;
    }

    public function hasPreviousPage() {
        return !$this->firstPage();
    }

    public function hasNextPage() {
        return !$this->lastPage();
    }

    public function previousPage() {
        $previous = $this->currentPage - 1;
        return ($previous > 0 ? $previous : false);
    }

    public function nextPage() {
        $next = $this->currentPage + 1;
        return ($next > $this->pages ? false : $next);
    }

    public function nextPageUri() {
        return Uri::make(array('path' => $this->baseUri . 'page/' . $this->nextPage()));
    }

    public function previousPageUri() {
        if ($this->previousPage() == 1) return Uri::make(array('path' => $this->baseUri));
        return Uri::make(array('path' => $this->baseUri . 'page/' . $this->previousPage()));
    }

    public function __debugInfo() {
        return array(
            'count' => $this->count,
            'length' => $this->length,
            'pages' => $this->pages,
            'currentPage' => $this->currentPage,
            'previousPage' => $this->previousPageUri()
        );
    }

}
