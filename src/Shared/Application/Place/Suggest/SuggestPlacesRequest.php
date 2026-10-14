<?php

declare(strict_types=1);

namespace App\Shared\Application\Place\Suggest;

use App\Shared\Domain\Model\Geo\PlaceKind;

class SuggestPlacesRequest
{
    public function __construct(
        public string $query,
        public PlaceKind $kind = PlaceKind::Any,
    ) {}
}
