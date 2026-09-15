<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RefrigerantGas;

interface RefrigerantGasRepository
{
    public function add(RefrigerantGas $refrigerantGas): void;

    public function remove(RefrigerantGas $refrigerantGas): void;

    public function find(RefrigerantGasId $refrigerantGasId): ?RefrigerantGas;

    /** @return array<RefrigerantGas> */
    public function findByFilter(RefrigerantGasFilter $filter): array;

    public function totalResultsByFilter(RefrigerantGasFilter $filter): int;
}
