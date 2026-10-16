<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class GlobalSearchService
{
    /** @param iterable<SearchProvider> $providers */
    public function __construct(
        #[AutowireIterator('shared.search.provider')]
        private readonly iterable $providers,
    ) {}

    public function isAvailableTo(User $user): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailableTo($user)) {
                return true;
            }
        }

        return false;
    }

    public function __invoke(GlobalSearchRequest $request, User $user): GlobalSearchResponse
    {
        if (!$this->isAvailableTo($user)) {
            throw new UnauthorizedActionException($this, Action::List);
        }

        $term = new SearchTerm($request->term);

        if (!$term->isSearchable()) {
            return new GlobalSearchResponse($term, [], false);
        }

        $providers = iterator_to_array($this->providers, false);
        usort($providers, fn(SearchProvider $a, SearchProvider $b): int => $a->category()->position <=> $b->category()->position);

        $categories = [];

        foreach ($providers as $provider) {
            if (!$provider->isAvailableTo($user)) {
                continue;
            }

            $results = $provider->search($term, $user, $request->limitPerCategory);

            if ([] === $results) {
                continue;
            }

            $categories[] = new SearchCategoryResults($provider->category(), $results);
        }

        return new GlobalSearchResponse($term, $categories, true);
    }
}
