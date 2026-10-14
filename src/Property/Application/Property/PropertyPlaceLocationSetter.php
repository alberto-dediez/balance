<?php

declare(strict_types=1);

namespace App\Property\Application\Property;

use App\Property\Domain\Model\Property\PropertyInfo;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class PropertyPlaceLocationSetter
{
    public function __construct(private readonly PlaceFinder $placeFinder) {}

    public function resolve(?PropertyInfo $info, ?PropertyInfo $current = null): ?PropertyInfo
    {
        if (!$info instanceof PropertyInfo) {
            return null;
        }

        $reference = trim($info->placeReference() ?? '');

        if ('' === $reference) {
            return $info->withPlace(null, null, $info->latitude(), $info->longitude());
        }

        if ($current instanceof PropertyInfo && $reference === $current->placeReference()) {
            return $info->withPlace($reference, $current->placeLabel(), $info->latitude(), $info->longitude());
        }

        $place = $this->placeFinder->resolve($reference, LocaleGetter::locale());

        if (null === $place) {
            throw new \InvalidArgumentException('The property location could not be resolved.');
        }

        return $info->withPlace(
            placeReference: $place->reference,
            placeLabel: $place->label,
            latitude: (string) $place->point->latitude,
            longitude: (string) $place->point->longitude,
        );
    }
}
