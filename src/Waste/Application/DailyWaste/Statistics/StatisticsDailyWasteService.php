<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class StatisticsDailyWasteService
{
    public function __construct(
        private DailyWasteRepository $dailyWasteRepository,
        private WasteManagerRepository $wasteManagerRepository,
        private PropertyRepository $propertyRepository,
        private WasteRepository $wasteRepository,
        private DailyWasteAuthorizer $authorizer
    ) {

    }

    public function __invoke(StatisticsDailyWasteRequest $request, User $user): StatisticsDailyWasteResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new DailyWaste(
                id: DailyWasteId::generate(),
                propertyId: $request->propertyId,
                accountId: $request->accountId,
            ),
            Action::List,
            $user
        );

        $dailyWastes = $this->dailyWasteRepository->findByFilter($request);

        list($byDate, $byPurpose, $byWaste, $byWasteManager, $byProperty) = $this->calculateStatistics($dailyWastes);

        return new StatisticsDailyWasteResponse(
            byDate: $byDate,
            byPurpose: $byPurpose,
            byWaste: $byWaste,
            byWasteManager: $byWasteManager,
            byProperty: $byProperty
        );
    }

    private function calculateStatistics(array $dailyWastes)
    {
        $byDate = $byPurpose = $byWaste = $byWasteManager = $byProperty = [];

        if (count($dailyWastes) == 0) {
            return [
                $byDate,
                $byPurpose,
                $byWaste,
                $byWasteManager,
                $byProperty
            ];
        }

        $total = 0;

        foreach ($dailyWastes as $dailyWaste) {
            $property = $this->propertyRepository->find($dailyWaste->propertyId());
            $waste = $this->wasteRepository->find($dailyWaste->wasteId());
            $wasteManager = $this->wasteManagerRepository->find($dailyWaste->wasteManagerId());

            if (null === $property || null === $waste || null === $wasteManager) {
                continue;
            }

            $total += $dailyWaste->load();

            //ByDate
            if (!isset($byDate[$dailyWaste->day()->format('Ymd')])) {
                $byDate[$dailyWaste->day()->format('Ymd')] = new ByDate($dailyWaste->day(), 0, 0);
            }

            $byDate[$dailyWaste->day()->format('Ymd')]->load += $dailyWaste->load();

            //ByProperty
            if (!isset($byProperty[(string) $dailyWaste->propertyId()])) {
                $byProperty[(string) $dailyWaste->propertyId()] = new ByProperty($property, 0, 0);
            }

            $byProperty[(string) $dailyWaste->propertyId()]->load += $dailyWaste->load();

            //ByWaste
            if (!isset($byWaste[(string) $dailyWaste->wasteId()])) {
                $byWaste[(string) $dailyWaste->wasteId()] = new ByWaste($waste, 0, 0);
            }

            $byWaste[(string) $dailyWaste->wasteId()]->load += $dailyWaste->load();

            //ByWasteManager
            if (!isset($byWasteManager[(string) $dailyWaste->wasteManagerId()])) {
                $byWasteManager[(string) $dailyWaste->wasteManagerId()] = new ByWasteManager($wasteManager, 0, 0);
            }

            $byWasteManager[(string) $dailyWaste->wasteManagerId()]->load += $dailyWaste->load();

            //ByPurpose
            if (!isset($byPurpose[$dailyWaste->purpose()->value])) {
                $byPurpose[$dailyWaste->purpose()->value] = new ByPurpose($dailyWaste->purpose(), 0, 0);
            }

            $byPurpose[$dailyWaste->purpose()->value]->load += $dailyWaste->load();
        }

        //ByDate
        array_walk($byDate, function (ByDate $byDate) use ($total) {
            $byDate->percentage = $total == 0 ? 0 : $byDate->load / $total;
        });

        usort($byDate, function (ByDate $byDateA, ByDate $byDateB) {
            return $byDateA->date <=> $byDateB->date;
        });

        //ByProperty
        array_walk($byProperty, function (ByProperty $byProperty) use ($total) {
            $byProperty->percentage = $total == 0 ? 0 : $byProperty->load / $total;
        });

        usort($byProperty, function (ByProperty $byPropertyA, ByProperty $byPropertyB) {
            return $byPropertyA->property->name() <=> $byPropertyB->property->name();
        });

        //ByWaste
        array_walk($byWaste, function (ByWaste $byWaste) use ($total) {
            $byWaste->percentage = $total == 0 ? 0 : $byWaste->load / $total;
        });

        usort($byWaste, function (ByWaste $byWasteA, ByWaste $byWasteB) {
            return $byWasteA->waste->name() <=> $byWasteB->waste->name();
        });

        //ByWasteManager
        array_walk($byWasteManager, function (ByWasteManager $byWasteManager) use ($total) {
            $byWasteManager->percentage = $total == 0 ? 0 : $byWasteManager->load / $total;
        });

        usort($byWasteManager, function (ByWasteManager $byWasteManagerA, ByWasteManager $byWasteManagerB) {
            return $byWasteManagerA->wasteManager->name() <=> $byWasteManagerB->wasteManager->name();
        });

        //ByPurpose
        array_walk($byPurpose, function (ByPurpose $byPurpose) use ($total) {
            $byPurpose->percentage = $total == 0 ? 0 : $byPurpose->load / $total;
        });

        return [
            array_values($byDate),
            array_values($byPurpose),
            array_values($byWaste),
            array_values($byWasteManager),
            array_values($byProperty)
        ];
    }
}
