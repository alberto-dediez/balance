<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

enum ExternalSource: string
{
    case Hotelbeds = 'hotelbeds';
    case Restel = 'restel';
    case Bioscore = 'bioscore';

    public function id(ExternalId $externalId): ?string
    {
        return match ($this) {
            self::Hotelbeds => $externalId->hotelbeds(),
            self::Restel => $externalId->restel(),
            self::Bioscore => $externalId->bioscore(),
        };
    }

    public function attribute(): ?string
    {
        return match ($this) {
            self::Hotelbeds => 'hotelbeds',
            self::Restel => 'restel',
            self::Bioscore => 'bioscore',
        };
    }
}
