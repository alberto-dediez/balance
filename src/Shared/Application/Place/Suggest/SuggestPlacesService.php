<?php

declare(strict_types=1);

namespace App\Shared\Application\Place\Suggest;

use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class SuggestPlacesService
{
    private const int MINIMUM_QUERY_LENGTH = 3;

    public function __construct(private readonly PlaceFinder $placeFinder) {}

    public function __invoke(SuggestPlacesRequest $request): SuggestPlacesResponse
    {
        $query = trim($request->query);

        if (mb_strlen($query) < self::MINIMUM_QUERY_LENGTH) {
            return new SuggestPlacesResponse([]);
        }

        return new SuggestPlacesResponse($this->placeFinder->suggest($query, $request->kind, LocaleGetter::locale()));
    }
}
