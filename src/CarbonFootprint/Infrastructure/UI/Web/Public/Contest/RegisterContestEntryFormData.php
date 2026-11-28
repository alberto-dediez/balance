<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest;

class RegisterContestEntryFormData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $country = null,
        public bool $adultConfirmed = false,
        public bool $consent = false,
    ) {}
}
