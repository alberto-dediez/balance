<?php

declare(strict_types=1);

namespace App\Shared\Application\Place\Suggest;

use App\Shared\Domain\Model\Geo\PlaceSuggestion;

class SuggestPlacesResponse
{
    /** @param list<PlaceSuggestion> $suggestions */
    public function __construct(public array $suggestions) {}
}
