<?php

namespace Formwork\Pages;

use Formwork\Cms\Site;
use Formwork\Data\AbstractCollection;
use Formwork\Data\Contracts\Paginable;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;
use RuntimeException;

class PageCollection extends AbstractCollection implements Paginable
{
    protected ?string $dataType = Page::class . '|' . Site::class;

    protected bool $associative = true;

    /**
     * Pagination related to the collection
     */
    protected Pagination $pagination;

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        array $data,
        protected PaginationFactory $paginationFactory,
    ) {
        parent::__construct($data);
    }

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
     * @param int $length      Number of items per page
     * @param int $currentPage Current page number
     */
    public function paginate(int $length, int $currentPage): self
    {
        $pagination = $this->paginationFactory->make($this, $length);
        $pagination->setCurrentPage($currentPage);

        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    public function extract(string $key, mixed $default = null): array
    {
        return $this->everyItem()->get($key, $default)->toArray();
    }

    /**
     * Get all the listed pages in the collection
     */
    public function listed(): static
    {
        return $this->filterBy('listed');
    }

    /**
     * Get all the published pages in the collection
     */
    public function published(): static
    {
        return $this->filterBy('status', 'published');
    }

    /**
     * Get all the routable pages in the collection
     *
     * @since 2.2.1
     */
    public function routable(): static
    {
        return $this->filterBy('routable');
    }

    /**
     * Get all the pages in the collection which allow children
     */
    public function allowingChildren(): static
    {
        return $this->filterBy('allowChildren');
    }

    /**
     * Get all the pages in the collection having the specified taxonomy terms
     *
     * @since 2.2.0
     *
     * @param array<string, list<string>> $taxonomy Taxonomy terms to filter by
     * @param bool                        $slug     Whether the provided terms are slugs
     */
    public function havingTaxonomy(array $taxonomy, bool $slug = false): static
    {
        return $this->filter(function (Page $page) use ($taxonomy, $slug): bool {
            foreach ($taxonomy as $taxonomyName => $terms) {
                $pageTerms = $page->taxonomy()[$taxonomyName] ?? [];
                if ($slug) {
                    $pageTerms = Arr::map($pageTerms, fn($term) => Str::slug($term));
                }
                if (array_intersect($terms, $pageTerms) === []) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Search pages in the collection
     *
     * For each page, a score is computed based on the number of matches found in its fields. The
     * final collection contains only pages with a score greater than zero, sorted by score in
     * descending order.
     *
     * The search looks for exact matches of the entire query as well as individual keywords
     * extracted from the query. The scoring weights for each field can be extended/overridden
     * via the `$weights` parameter. Default weights for common fields are already provided
     * (title: 8, summary: 4, content: 3, author: 2, uri: 1).
     *
     * The search is case-insensitive and ignores HTML tags in the page fields.
     *
     * @param string             $query             Query to search for
     * @param int                $minimumLength     Minimum query length
     * @param int                $maxKeywordMatches Maximum number of keyword matches to count per field for scoring purposes
     * @param array<string, int> $weights           Weights for each field to consider in the scoring
     *
     * @throws RuntimeException If whitespace normalization fails
     */
    public function search(string $query, int $minimumLength = 4, int $maxKeywordMatches = 3, array $weights = []): static
    {
        if (!extension_loaded('mbstring')) {
            throw new RuntimeException(sprintf('%s() requires the extension "mbstring" to be enabled', __METHOD__));
        }

        $query = preg_replace(['/\s+/u', '/^\s+|\s+$/u'], [' ', ''], $query)
            ?? throw new RuntimeException(sprintf('Whitespace normalization failed with error: %s', preg_last_error_msg()));

        if (mb_strlen($query) < $minimumLength) {
            $pageCollection = clone $this;
            $pageCollection->data = [];
            return $pageCollection;
        }

        $keywords = explode(' ', $query);

        $keywords = array_filter($keywords, fn(string $item): bool => mb_strlen($item) >= $minimumLength);

        if ($keywords === []) {
            $pageCollection = clone $this;
            $pageCollection->data = [];
            return $pageCollection;
        }

        // Use case-sensitive regex on lowercased text for better performance
        $queryLower = mb_strtolower($query);
        $keywordsLower = array_map(mb_strtolower(...), $keywords);

        $queryRegex = '/\b' . preg_quote($queryLower, '/') . '\b/u';
        $escapedKeywords = array_map(fn($keyword) => preg_quote($keyword, '/'), $keywordsLower);
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $escapedKeywords) . '\b)/u';

        $weights += [
            'title'   => 8,
            'summary' => 4,
            'content' => 3,
            'author'  => 2,
            'uri'     => 1,
        ];

        $pageCollection = clone $this;

        foreach ($pageCollection->data as $page) {
            $score = 0;

            foreach ($weights as $key => $weight) {
                $value = Str::removeHTML((string) $page->get($key));

                if ($value === '') {
                    continue;
                }

                $valueLower = mb_strtolower($value);

                $queryMatches = (int) preg_match_all($queryRegex, $valueLower);
                $keywordsMatches = (int) preg_match_all($keywordsRegex, $valueLower);

                $score += ($queryMatches * 2 + min($keywordsMatches, $maxKeywordMatches)) * $weight;
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filterBy('score')->sortBy('score', direction: SORT_DESC);
    }

    /**
     * Get all the pages in the collection without the children of the specified one
     */
    public function withoutChildren(Page $page): static
    {
        return $this->difference($page->children());
    }

    /**
     * Get all the pages in the collection without the specified one and its children
     */
    public function withoutPageAndChildren(Page $page): static
    {
        return $this->without($page)->difference($page->children());
    }

    /**
     * Get all the pages in the collection without the descendants of the specified one
     */
    public function withoutDescendants(Page $page): static
    {
        return $this->difference($page->descendants());
    }

    /**
     * Get all the pages in the collection without the specified one and its descendants
     */
    public function withoutPageAndDescendants(Page $page): static
    {
        return $this->without($page)->difference($page->descendants());
    }

    /**
     * Get all the pages in the collection without the parent of the specified one
     */
    public function withoutParent(Page $page): static
    {
        return $this->without($page->parent());
    }

    /**
     * Get all the pages in the collection without the specified one and its parent
     */
    public function withoutPageAndParent(Page $page): static
    {
        return $this->without($page)->without($page->parent());
    }

    /**
     * Get all the pages in the collection without the ancestors of the specified one
     */
    public function withoutAncestors(Page $page): static
    {
        return $this->difference($page->ancestors());
    }

    /**
     * Get all the pages in the collection without the specified one and its ancestors
     */
    public function withoutPageAndAncestors(Page $page): static
    {
        return $this->without($page)->difference($page->ancestors());
    }

    /**
     * Get all the pages in the collection without the siblings of the specified one
     */
    public function withoutSiblings(Page $page): static
    {
        return $this->difference($page->siblings());
    }

    /**
     * Get all the pages in the collection without the specified one and its siblings
     */
    public function withoutPageAndSiblings(Page $page): static
    {
        return $this->without($page)->difference($page->siblings());
    }
}
