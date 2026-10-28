<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyGroupAssignment
{
    public function __construct(
        public string $organizationId,
        public ?string $group,
        public ?string $bioscoreHotelId,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $address,
        public ?string $postalCode,
    ) {}
}
