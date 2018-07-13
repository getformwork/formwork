<?php

namespace Formwork\Core;

use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;

class PageCollection extends Collection
{
    protected $pagination;

    public function pagination()
    {
        return $this->pagination;
    }

    public function reverse()
    {
        $pageCollection = clone $this;
        $pageCollection->items = array_reverse($pageCollection->items);
        return $pageCollection;
    }

    public function slice($offset, $length = null)
    {
        $pageCollection = clone $this;
        $pageCollection->items = array_slice($pageCollection->items, $offset, $length);
        return $pageCollection;
    }

    public function remove(Page $element)
    {
        $pageCollection = clone $this;
        foreach ($pageCollection->items as $key => $item) {
            if ($item->id() == $element->id()) {
                unset($pageCollection->items[$key]);
            }
        }
        return $pageCollection;
    }

    public function paginate($length)
    {
        $pagination = new Pagination($this->count(), $length);
        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    public function filter($property, $value = true, $process = null)
    {
        $pageCollection = clone $this;

        $pageCollection->items = array_filter($pageCollection->items, function ($item) use ($property, $value, $process) {
            if ($item->has($property)) {
                $propertyValue = $item->get($property);

                if (is_callable($process)) {
                    $propertyValue = is_array($propertyValue) ? array_map($process, $propertyValue) : $process($propertyValue);
                    $value = $process($value);
                }

                if (is_array($propertyValue)) {
                    return in_array($value, $propertyValue);
                }
                return $propertyValue == $value;
            }

            return false;
        });

        return $pageCollection;
    }

    public function sort($property = 'id', $direction = SORT_ASC)
    {
        $pageCollection = clone $this;

        if ($pageCollection->count() <= 1) {
            return $pageCollection;
        }

        if ($direction == SORT_DESC || strtolower($direction) == 'desc' || $direction == -1) {
            $direction = -1;
        } else {
            $direction = 1;
        }

        usort($pageCollection->items, function ($item1, $item2) use ($property, $direction) {
            return $direction * strnatcasecmp($item1->get($property), $item2->get($property));
        });

        return $pageCollection;
    }

    public function search($query, $min = 4)
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (strlen($query) < $min) {
            return new PageCollection(array());
        }

        $keywords = explode(' ', $query);
        $keywords = array_diff($keywords, (array) Formwork::instance()->option('search.stopwords'));
        $keywords = array_filter($keywords, function ($item) use ($min) {
            return strlen($item) > $min;
        });

        $queryRegex = '/\b' . preg_quote($query, '/') . '\b/iu';
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $keywords) . '\b)/iu';

        $scores = array(
            'title'    => 8,
            'summary'  => 4,
            'content'  => 3,
            'author'   => 2,
            'uri'      => 1
        );

        $pageCollection = clone $this;

        foreach ($pageCollection->items as $page) {
            $score = 0;
            foreach (array_keys($scores) as $key) {
                $value = html_entity_decode($page->get($key));

                $queryMatches = preg_match_all($queryRegex, $value);
                $keywordsMatches = empty($keywords) ? 0 : preg_match_all($keywordsRegex, $value);

                $score += ($queryMatches * 2 + min($keywordsMatches, 3)) * $scores[$key];
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filter('score')->sort('score', SORT_DESC);
    }

    public static function fromPath($path, $recursive = false)
    {
        $path = FileSystem::normalize($path);
        $pages = array();

        foreach (FileSystem::listDirectories($path) as $dir) {
            $pagePath = $path . $dir . DS;

            if ($dir[0] !== '_' && FileSystem::isDirectory($pagePath)) {
                if (isset(Site::$storage[$pagePath])) {
                    $page = Site::$storage[$pagePath];
                } else {
                    $page = new Page($pagePath);
                    Site::$storage[$pagePath] = $page;
                }

                if (!$page->empty()) {
                    $pages[] = $page;
                }

                if ($recursive) {
                    $pages = array_merge($pages, self::fromPath($pagePath, true)->toArray());
                }
            }
        }

        $pages = new static($pages);
        return $pages->sort();
    }

    public function __debugInfo()
    {
        return array(
            'items' => $this->items
        );
    }
}
