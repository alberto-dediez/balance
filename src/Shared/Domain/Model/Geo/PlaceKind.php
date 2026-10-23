<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

enum PlaceKind: string
{
    case City = 'city';
    case Lodging = 'lodging';
    case Any = 'any';
    case Region = 'region';
}
