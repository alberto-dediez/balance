<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Property\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\WasteManager\WasteManager;

class StatisticsDailyWasteResponse
{
    public function __construct(
        /** @var ByMonth[] */
        public array $byMonth,

        /** @var ByDate[] */
        public array $byDate,

        /** @var ByPurpose[] */
        public array $byPurpose,

        /** @var ByWaste[] */
        public array $byWaste,

        /** @var ByWasteManager[] */
        public array $byWasteManager,

        /** @var ByProperty[] */
        public array $byProperty,
    ) {}

    /** @return \DateTimeImmutable[] */
    public function dates(): array
    {
        return array_map(
            fn(ByDate $element): \DateTimeImmutable => $element->date,
            $this->byDate,
        );
    }

    /** @return \DateTimeImmutable[] */
    public function months(): array
    {
        return array_map(
            fn(ByMonth $element): \DateTimeImmutable => $element->month,
            $this->byMonth,
        );
    }

    /** @return Purpose[] */
    public function purposes(): array
    {
        return array_map(fn(ByPurpose $element): Purpose => $element->purpose, $this->byPurpose);
    }

    /** @return Waste[] */
    public function wastes(): array
    {
        return array_map(fn(ByWaste $element): Waste => $element->waste, $this->byWaste);
    }

    /** @return WasteManager[] */
    public function wasteManagers(): array
    {
        return array_map(fn(ByWasteManager $element): WasteManager => $element->wasteManager, $this->byWasteManager);
    }

    /** @return Property[] */
    public function properties(): array
    {
        return array_map(fn(ByProperty $element): Property => $element->property, $this->byProperty);
    }

    /** @return float[][] */
    public function byPropertyAndMonths(): array
    {
        $result = [];

        if (0 === count($this->byProperty)) {
            return $result;
        }

        foreach ($this->byProperty as $property) {
            $result[] = array_map(fn(ByMonth $byMonth): float => $byMonth->load, $property->months);
        }

        return $result;
    }
}
