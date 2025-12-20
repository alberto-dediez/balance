<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Container\Purpose;
use App\Property\Domain\Model\Property\Property;
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
        public array $byProperty
    ) {

    }

    /** @return \DateTimeImmutable[] */
    public function dates(): array
    {
        return array_map(
            function (ByDate $element) {
                return $element->date;
            },
            $this->byDate
        );
    }

    /** @return \DateTimeImmutable[] */
    public function months(): array
    {
        return array_map(
            function (ByMonth $element) {
                return $element->month;
            },
            $this->byMonth
        );
    }

    /** @return Purpose[] */
    public function purposes(): array
    {
        return array_map(function (ByPurpose $element) {
            return $element->purpose;
        }, $this->byPurpose);
    }

    /** @return Waste[] */
    public function wastes(): array
    {
        return array_map(function (ByWaste $element) {
            return $element->waste;
        }, $this->byWaste);
    }

    /** @return WasteManager[] */
    public function wasteManagers(): array
    {
        return array_map(function (ByWasteManager $element) {
            return $element->wasteManager;
        }, $this->byWasteManager);
    }

    /** @return Property[] */
    public function properties(): array
    {
        return array_map(function (ByProperty $element) {
            return $element->property;
        }, $this->byProperty);
    }

    /** @return float[][] */
    public function byPropertyAndMonths(): array
    {
        $result = [];

        if (0 === count($this->byProperty)) {
            return $result;
        }

        foreach ($this->byProperty as $property) {
            $result[] = array_map(function (ByMonth $byMonth) {
                return $byMonth->load;
            }, $property->months);
        }

        return $result;
    }
}
