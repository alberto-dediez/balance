<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Search;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Search\SearchCategory;
use App\Shared\Application\Search\SearchProvider;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;

class FakeSearchProvider implements SearchProvider
{
    public ?int $lastLimit = null;

    public int $calls = 0;

    /** @param list<SearchResult> $results */
    public function __construct(
        private readonly SearchCategory $category,
        private readonly bool $available = true,
        private readonly array $results = [],
    ) {}

    public static function withResults(string $key, int $position, int $results = 1, bool $available = true): self
    {
        return new self(
            new SearchCategory($key, ucfirst($key), 'solar:magnifer-line-duotone', $position),
            $available,
            array_map(
                fn(int $index): SearchResult => new SearchResult(sprintf('%s %d', $key, $index), null, sprintf('%s_edit', $key), ['id' => (string) $index]),
                0 === $results ? [] : range(1, $results),
            ),
        );
    }

    public function category(): SearchCategory
    {
        return $this->category;
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->available;
    }

    public function search(SearchTerm $term, User $user, int $limit): array
    {
        ++$this->calls;
        $this->lastLimit = $limit;

        return $this->available ? $this->results : [];
    }
}
