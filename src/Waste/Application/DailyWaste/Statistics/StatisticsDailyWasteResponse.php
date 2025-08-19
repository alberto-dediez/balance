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
        return array_map(
            function (ByDate $element) {
                return $element->date;
            }, 
            $this->byDate
        );
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
        return array_map(function (ByWaste $element) {
            return $element->waste;
        }, $this->byWaste);
    }

    /** @var WasteManager[] */
    public function wasteManagers(): array
    {
        return array_map(function (ByWasteManager $element) {
            return $element->wasteManager;
        }, $this->byWasteManager);
    }

    /** @var Property[] */
    public function properties(): array
    {
        return array_map(function (ByProperty $element) {
            return $element->property;
        }, $this->byProperty);
    }
}