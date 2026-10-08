<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.search.provider')]
interface SearchProvider
{
    public function category(): SearchCategory;

    public function isAvailableTo(User $user): bool;

    /** @return list<SearchResult> */
    public function search(SearchTerm $term, User $user, int $limit): array;
}
