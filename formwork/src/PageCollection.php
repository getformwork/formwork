<?php

namespace Formwork;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

class PageCollection extends AbstractCollection
{
    /**
     * Default property used to sort pages
     */
    protected const DEFAULT_SORT_PROPERTY = 'relativePath';

    protected ?string $dataType = AbstractPage::class;

    /**
     * Pagination related to the collection
     */
    protected Pagination $pagination;

    /**
     * Return the Pagination object related to the collection
     */
    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Paginate the collection
     *
     * @param int $length Number of items in the pagination
     */
    public function paginate(int $length): static
    {
        $pagination = new Pagination($this->count(), $length);
        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    /**
     * Return an array containing the specified property of each collection item
     */
    public function pluck(string $property): array
    {
        $result = [];

        foreach ($this->data as $page) {
            $result[] = $page->get($property);
        }

        return $result;
    }

    /**
     * Filter collection items
     *
     * @param string   $property Property to find in filtered items
     * @param          $value    Value to check in filtered items (default: true)
     * @param callable $process  Callable to process items before filtering
     */
    public function filterBy(string $property, $value = true, callable $process = null): static
    {
        return $this->filter(static function (Page $item) use ($property, $value, $process): bool {
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
    }

    /**
     * Sort collection items
     */
    public function sortBy(
        string $property = self::DEFAULT_SORT_PROPERTY,
        int $direction = SORT_ASC,
        int $type = SORT_NATURAL,
        bool $caseSensitive = false,
        bool $preserveKeys = true
    ): static {
        return parent::sort($direction, $type, $this->pluck($property), $caseSensitive, $preserveKeys);
    }

    /**
     * Search pages in the collection
     *
     * @param string $query Query to search for
     * @param int    $min   Minimum query length (default: 4)
     */
    public function search(string $query, int $min = 4): static
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (strlen($query) < $min) {
            return new static();
        }

        $keywords = explode(' ', $query);
        $keywords = array_diff($keywords, (array) Formwork::instance()->config()->get('search.stopwords'));
        $keywords = array_filter($keywords, static fn (string $item): bool => strlen($item) > $min);

        $queryRegex = '/\b' . preg_quote($query, '/') . '\b/iu';
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $keywords) . '\b)/iu';

        $scores = [
            'title'    => 8,
            'summary'  => 4,
            'content'  => 3,
            'author'   => 2,
            'uri'      => 1
        ];

        $pageCollection = clone $this;

        foreach ($pageCollection->data as $page) {
            $score = 0;
            foreach (array_keys($scores) as $key) {
                $value = Str::removeHTML((string) $page->get($key));

                $queryMatches = preg_match_all($queryRegex, $value);
                $keywordsMatches = empty($keywords) ? 0 : preg_match_all($keywordsRegex, $value);

                $score += ($queryMatches * 2 + min($keywordsMatches, 3)) * $scores[$key];
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filterBy('score')->sortBy('score', direction: SORT_DESC);
    }

    /**
     * Create a collection getting pages from a given path
     *
     * @param bool $recursive Whether to recursively search for pages
     */
    public static function fromPath(string $path, bool $recursive = false): self
    {
        $pages = [];

        foreach (FileSystem::listDirectories($path) as $dir) {
            $pagePath = FileSystem::joinPaths($path, $dir, DS);

            if ($dir[0] !== '_' && FileSystem::isDirectory($pagePath)) {
                $page = Formwork::instance()->site()->retrievePage($pagePath);

                if (!$page->isEmpty()) {
                    $pages[] = $page;
                }

                if ($recursive) {
                    $pages = array_merge($pages, static::fromPath($pagePath, true)->toArray());
                }
            }
        }

        $pages = new static($pages);

        return $pages->sortBy('path');
    }
}
