<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreConsumption;

final readonly class BioscoreConsumption
{
    public function __construct(
        public int $itemId,
        public string $itemName,
        public string $itemUnit,
        public ?bool $usableForFacilities,
        public ?bool $usableForTransportation,
        public float $consumption,
        public float $amountEur,
    ) {}
}
