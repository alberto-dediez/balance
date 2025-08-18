<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Container\Purpose;
use App\Property\Domain\Model\Property\Property;
use App\Waste\Domain\Model\WasteManager\WasteManager;

class StatisticsDailyWasteResponse
{
    public function __construct(
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

    /** @var \DateTimeImmutable[] */
    public function dates(): array
    {
        $dates = array_map(
            function (ByDate $element) {
                return $element->date;
            }, 
            $this->byDate
        );

        usort($dates, function (\DateTimeImmutable $a, \DateTimeImmutable $b) {
            return $a <=> $b;
        });

        return $dates;
    }

    /** @var Purpose[] */
    public function purposes(): array
    {
        return array_map(function (ByPurpose $element) {
            return $element->purpose;
        }, $this->byPurpose);
    }

    /** @var Waste[] */
    public function wastes(): array
    {
        $wastes = array_map(function (ByWaste $element) {
            return $element->waste;
        }, $this->byWaste);

        usort($wastes, function (Waste $a, Waste $b) {
            return $a->name() <=> $b->name();
        });

        return $wastes;
    }

    /** @var WasteManager[] */
    public function wasteManagers(): array
    {
        $wasteManagers = array_map(function (ByWasteManager $element) {
            return $element->wasteManager;
        }, $this->byWasteManager);

        usort($wasteManagers, function (WasteManager $a, WasteManager $b) {
            return $a->name() <=> $b->name();
        });

        return $wasteManagers;
    }

    /** @var Property[] */
    public function properties(): array
    {
        $properties = array_map(function (ByProperty $element) {
            return $element->property;
        }, $this->byProperty);

        usort($properties, function (Property $a, Property $b) {
            return $a->name() <=> $b->name();
        });

        return $properties;
    }
}