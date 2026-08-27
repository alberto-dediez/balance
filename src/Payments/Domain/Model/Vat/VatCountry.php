<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

final class VatCountry
{
    public function __construct(
        public readonly string $iso,
        public readonly float $rate,
        public readonly bool $europeanUnion,
    ) {}
}
