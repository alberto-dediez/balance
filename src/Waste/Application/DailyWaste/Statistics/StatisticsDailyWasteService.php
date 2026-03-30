<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class StatisticsDailyWasteService
{
    public function __construct(
        private readonly DailyWasteRepository $dailyWasteRepository,
        private readonly WasteManagerRepository $wasteManagerRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly WasteRepository $wasteRepository,
        private readonly DailyWasteAuthorizer $authorizer,
    ) {}

    public function __invoke(StatisticsDailyWasteRequest $request, User $user): StatisticsDailyWasteResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new DailyWaste(
                id: DailyWasteId::generate(),
                propertyId: null,
                accountId: $request->accountId,
            ),
            Action::List,
            $user,
        );

        $dailyWastes = $this->dailyWasteRepository->findByFilter($request->filter());

        [$byMonth, $byDate, $byPurpose, $byWaste, $byWasteManager, $byProperty] = $this->calculateStatistics($dailyWastes);

        return new StatisticsDailyWasteResponse(
            byMonth: $byMonth,
            byDate: $byDate,
            byPurpose: $byPurpose,
            byWaste: $byWaste,
            byWasteManager: $byWasteManager,
            byProperty: $byProperty,
        );
    }

    /**
     * @param DailyWaste[] $dailyWastes
     *
     * @return mixed[][]
     */
    private function calculateStatistics(array $dailyWastes): array
    {
        $byMonth = $byDate = $byPurpose = $byWaste = $byWasteManager = $byProperty = [];

        if (0 === count($dailyWastes)) {
            return [
                $byMonth,
                $byDate,
                $byPurpose,
                $byWaste,
                $byWasteManager,
                $byProperty,
            ];
        }

        $months = $this->getMonths($dailyWastes);

        $byMonth = array_reduce($months, function (array $result, \DateTimeImmutable $month): array {
            $result[$month->format('Ym')] = new ByMonth(
                month: $month,
                load: 0,
                percentage: 0,
            );

            return $result;
        }, []);

        $total = 0;

        foreach ($dailyWastes as $dailyWaste) {
            $dailyWastePropertyId = $dailyWaste->propertyId();
            $dailyWasteWasteId = $dailyWaste->wasteId();
            $dailyWasteWasteManagerId = $dailyWaste->wasteManagerId();

            if (null === $dailyWastePropertyId || null === $dailyWasteWasteId || null === $dailyWasteWasteManagerId) {
                continue;
            }

            $property = $this->propertyRepository->find($dailyWastePropertyId);
            $waste = $this->wasteRepository->find($dailyWasteWasteId);
            $wasteManager = $this->wasteManagerRepository->find($dailyWasteWasteManagerId);
            if (!$property instanceof Property) {
                continue;
            }
            if (!$waste instanceof Waste) {
                continue;
            }
            if (!$wasteManager instanceof WasteManager) {
                continue;
            }

            $total += $dailyWaste->load();

            // ByMonth
            $byMonth[$dailyWaste->day()->format('Ym')]->load += $dailyWaste->load();

            // ByDate
            if (!isset($byDate[$dailyWaste->day()->format('Ymd')])) {
                $byDate[$dailyWaste->day()->format('Ymd')] = new ByDate($dailyWaste->day(), 0, 0);
            }

            $byDate[$dailyWaste->day()->format('Ymd')]->load += $dailyWaste->load();

            // ByProperty
            if (!isset($byProperty[(string) $dailyWaste->propertyId()])) {
                $byProperty[(string) $dailyWaste->propertyId()] = new ByProperty(
                    property: $property,
                    load: 0,
                    percentage: 0,
                    months: array_reduce($months, function (array $result, \DateTimeImmutable $month): array {
                        $result[$month->format('Ym')] = new ByMonth(
                            month: $month,
                            load: 0,
                            percentage: 0,
                        );

                        return $result;
                    }, []),
                );
            }

            $byProperty[(string) $dailyWaste->propertyId()]->load += $dailyWaste->load();
            $byProperty[(string) $dailyWaste->propertyId()]->months[$dailyWaste->day()->format('Ym')]->load += $dailyWaste->load();

            // ByWaste
            if (!isset($byWaste[(string) $dailyWaste->wasteId()])) {
                $byWaste[(string) $dailyWaste->wasteId()] = new ByWaste($waste, 0, 0);
            }

            $byWaste[(string) $dailyWaste->wasteId()]->load += $dailyWaste->load();

            // ByWasteManager
            if (!isset($byWasteManager[(string) $dailyWaste->wasteManagerId()])) {
                $byWasteManager[(string) $dailyWaste->wasteManagerId()] = new ByWasteManager($wasteManager, 0, 0);
            }

            $byWasteManager[(string) $dailyWaste->wasteManagerId()]->load += $dailyWaste->load();

            // ByPurpose
            $dailyWastePurpose = $dailyWaste->purpose();
            if (!isset($byPurpose[$dailyWastePurpose->value])) {
                $byPurpose[$dailyWastePurpose->value] = new ByPurpose($dailyWastePurpose, 0, 0);
            }

            $byPurpose[$dailyWastePurpose->value]->load += $dailyWaste->load();
        }

        // ByMonth
        array_walk($byMonth, function (ByMonth $byMonth) use ($total): void {
            $byMonth->percentage = 0 == $total ? 0 : $byMonth->load / $total;
        });

        usort($byMonth, fn(ByMonth $byMonthA, ByMonth $byMonthB) => $byMonthA->month <=> $byMonthB->month);

        // ByDate
        array_walk($byDate, function (ByDate $byDate) use ($total): void {
            $byDate->percentage = 0 == $total ? 0 : $byDate->load / $total;
        });

        usort($byDate, fn(ByDate $byDateA, ByDate $byDateB) => $byDateA->date <=> $byDateB->date);

        // ByProperty
        array_walk($byProperty, function (ByProperty $byProperty) use ($total): void {
            $byProperty->percentage = 0 == $total ? 0 : $byProperty->load / $total;

            $totalOfProperty = $byProperty->load;

            array_walk($byProperty->months, function (ByMonth $byMonth) use ($totalOfProperty): void {
                $byMonth->percentage = 0 == $totalOfProperty ? 0 : $byMonth->load / $totalOfProperty;
            });

            usort($byProperty->months, fn(ByMonth $byMonthA, ByMonth $byMonthB) => $byMonthA->month <=> $byMonthB->month);
        });

        usort($byProperty, fn(ByProperty $byPropertyA, ByProperty $byPropertyB) => $byPropertyA->property->name() <=> $byPropertyB->property->name());

        // ByWaste
        array_walk($byWaste, function (ByWaste $byWaste) use ($total): void {
            $byWaste->percentage = 0 == $total ? 0 : $byWaste->load / $total;
        });

        usort($byWaste, fn(ByWaste $byWasteA, ByWaste $byWasteB) => $byWasteA->waste->name() <=> $byWasteB->waste->name());

        // ByWasteManager
        array_walk($byWasteManager, function (ByWasteManager $byWasteManager) use ($total): void {
            $byWasteManager->percentage = 0 == $total ? 0 : $byWasteManager->load / $total;
        });

        usort($byWasteManager, fn(ByWasteManager $byWasteManagerA, ByWasteManager $byWasteManagerB) => $byWasteManagerA->wasteManager->name() <=> $byWasteManagerB->wasteManager->name());

        // ByPurpose
        array_walk($byPurpose, function (ByPurpose $byPurpose) use ($total): void {
            $byPurpose->percentage = 0 == $total ? 0 : $byPurpose->load / $total;
        });

        return [
            $byMonth,
            $byDate,
            $byPurpose,
            $byWaste,
            $byWasteManager,
            $byProperty,
        ];
    }

    /**
     * @param DailyWaste[] $dailyWastes
     *
     * @return \DateTimeImmutable[]
     */
    private function getMonths(array $dailyWastes): array
    {
        $startDate = array_reduce($dailyWastes, fn($start, DailyWaste $dailyWaste) => (null === $start || $start > $dailyWaste->day()) ? $dailyWaste->day() : $start);

        $startDate = (new \DateTimeImmutable($startDate->format('Y-m-01')));

        $endDate = array_reduce($dailyWastes, fn($start, DailyWaste $dailyWaste) => (null === $start || $start < $dailyWaste->day()) ? $dailyWaste->day() : $start);

        $endDate = (new \DateTimeImmutable($endDate->format('Y-m-t')));

        return iterator_to_array(
            new \DatePeriod(
                $startDate,
                new \DateInterval('P1M'),
                $endDate,
            ),
        );
    }
}
