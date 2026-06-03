<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;

class AbnormallyHighValueAlertChecker implements AlertChecker
{
    public const DEFAULT_DEVIATION_MULTIPLIER = 2.0;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::AbnormallyHigh;
    }

    /**
     * @param AlertConfigurationItem[] $rules
     *
     * @return Alert[]
     */
    public function check(
        AccountId $accountId,
        array $rules,
        \DateTimeImmutable $checkDate,
    ): array {
        $alerts = [];
        $yesterday = $checkDate->modify('-1 day');
        $thirtyDaysAgo = $checkDate->modify('-30 days');

        foreach ($rules as $rule) {
            $multiplier = (float) ($rule->parameters()['deviationMultiplier'] ?? self::DEFAULT_DEVIATION_MULTIPLIER);

            if (null !== $rule->propertyId()) {
                $property = $this->propertyRepository->find($rule->propertyId());
                $properties = null !== $property ? [$property] : [];
            } else {
                $properties = $this->propertyRepository->findByFilter(new PropertyFilter(
                    accountId: $accountId,
                    limit: 0,
                ));
            }

            if (0 === count($properties)) {
                continue;
            }

            foreach ($properties as $property) {
                $recentData = $this->dailyWasteRepository->findByFilter(new DailyWasteFilter(
                    properties: [$property->id()],
                    accountId: $accountId,
                    dayFrom: $thirtyDaysAgo,
                    limit: 0,
                ));

                $byWaste = $this->groupByWaste($recentData);

                if (null !== $rule->wasteId()) {
                    $ruleWasteKey = (string) $rule->wasteId();
                    $byWaste = isset($byWaste[$ruleWasteKey]) ? [$ruleWasteKey => $byWaste[$ruleWasteKey]] : [];
                }

                foreach ($byWaste as $wasteKey => [$wasteId, $dailyWastes]) {
                    $yesterdayEntry = $this->findForDay($dailyWastes, $yesterday);

                    if (null === $yesterdayEntry) {
                        continue;
                    }

                    $historicalValues = array_filter(
                        array_map(fn(DailyWaste $dw) => (float) $dw->load(), $dailyWastes),
                        fn(float $v) => $v > 0,
                    );

                    if (count($historicalValues) < 2) {
                        continue;
                    }

                    $mean = array_sum($historicalValues) / count($historicalValues);
                    $variance = array_sum(array_map(fn(float $v) => ($v - $mean) ** 2, $historicalValues)) / count($historicalValues);
                    $stddev = sqrt($variance);

                    $threshold = $mean + $multiplier * $stddev;
                    $value = (float) $yesterdayEntry->load();

                    if ($value > $threshold) {
                        $alert = new Alert(id: AlertId::generate());
                        $alert->detect(
                            type: AlertType::AbnormallyHigh,
                            propertyId: $property->id(),
                            wasteId: $wasteId,
                            accountId: $accountId,
                            detectedAt: $checkDate,
                            from: $thirtyDaysAgo,
                            to: $checkDate,
                            value: $value,
                            threshold: $threshold,
                        );

                        $alerts[] = $alert;
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * @param DailyWaste[] $dailyWastes
     *
     * @return array<string, array{WasteId, DailyWaste[]}>
     */
    private function groupByWaste(array $dailyWastes): array
    {
        $grouped = [];

        foreach ($dailyWastes as $dw) {
            $wasteId = $dw->wasteId();

            if (null === $wasteId) {
                continue;
            }

            $key = (string) $wasteId;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [$wasteId, []];
            }

            $grouped[$key][1][] = $dw;
        }

        return $grouped;
    }

    /**
     * @param DailyWaste[] $dailyWastes
     */
    private function findForDay(array $dailyWastes, \DateTimeImmutable $day): ?DailyWaste
    {
        foreach ($dailyWastes as $dw) {
            if ($dw->day()->format('Y-m-d') === $day->format('Y-m-d')) {
                return $dw;
            }
        }

        return null;
    }
}
