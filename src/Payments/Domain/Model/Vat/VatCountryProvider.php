<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

interface VatCountryProvider
{
    public function findByIso(string $iso): ?VatCountry;
}
