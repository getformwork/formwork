<?php

namespace Formwork\Pages;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Contracts\Paginable;
use Formwork\Site;
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
    public function paginate(int $length, int $currentPage): self
    {
        $pagination = new Pagination($this, $length);
        $pagination->setCurrentPage($currentPage);

        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    /**
     * @return array<string, mixed>
     */
    public function pluck(string $key, mixed $default = null): array
    {
        return $this->everyItem()->get($key, $default)->toArray();
    }

    public function listed(): static
    {
        return $this->filterBy('listed');
    }

    public function published(): static
    {
        return $this->filterBy('status', 'published');
    }

    public function allowingChildren(): static
    {
        return $this->filterBy('allowChildren');
    }

    /**
     * Search pages in the collection
     *
     * @param string $query Query to search for
     * @param int    $min   Minimum query length (default: 4)
     */
    public function search(string $query, int $min = 4): static
    {
        $query = preg_replace(['/\s+/u', '/^\s+|\s+$/u'], [' ', ''], $query)
            ?? throw new RuntimeException(sprintf('Whitespace normalization failed with error: %s', preg_last_error_msg()));

        if (strlen($query) < $min) {
            $pageCollection = clone $this;
            $pageCollection->data = [];
        }

        $keywords = explode(' ', $query);

        $keywords = array_filter($keywords, fn (string $item): bool => strlen($item) > $min);

        $queryRegex = '/\b' . preg_quote($query, '/') . '\b/iu';
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $keywords) . '\b)/iu';

        $scores = [
            'title'   => 8,
            'summary' => 4,
            'content' => 3,
            'author'  => 2,
            'uri'     => 1,
        ];

        $pageCollection = clone $this;

        foreach ($pageCollection->data as $page) {
            $score = 0;
            foreach (array_keys($scores) as $key) {
                $value = Str::removeHTML((string) $page->get($key));

                $queryMatches = preg_match_all($queryRegex, $value);
                $keywordsMatches = $keywords === [] ? 0 : preg_match_all($keywordsRegex, $value);

                $score += ($queryMatches * 2 + min($keywordsMatches, 3)) * $scores[$key];
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filterBy('score')->sortBy('score', direction: SORT_DESC);
    }

    public function withoutChildren(Page $page): static
    {
        return $this->difference($page->children());
    }

    public function withoutPageAndChildren(Page $page): static
    {
        return $this->without($page)->difference($page->children());
    }

    public function withoutDescendants(Page $page): static
    {
        return $this->difference($page->descendants());
    }

    public function withoutPageAndDescendants(Page $page): static
    {
        return $this->without($page)->difference($page->descendants());
    }

    public function withoutParent(Page $page): static
    {
        return $this->without($page->parent());
    }

    public function withoutPageAndParent(Page $page): static
    {
        return $this->without($page)->without($page->parent());
    }

    public function withoutAncestors(Page $page): static
    {
        return $this->difference($page->ancestors());
    }

    public function withoutPageAndAncestors(Page $page): static
    {
        return $this->without($page)->difference($page->ancestors());
    }

    public function withoutSiblings(Page $page): static
    {
        return $this->difference($page->siblings());
    }

    public function withoutPageAndSiblings(Page $page): static
    {
        return $this->without($page)->difference($page->siblings());
    }
}
