<?php

declare(strict_types=1);

namespace App\Shared\Application\Place;

interface CountryRegionCode
{
    public function alpha2(string $isoCode): ?string;
}
