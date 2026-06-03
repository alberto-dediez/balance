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

class ThresholdExceededAlertChecker implements AlertChecker
{
    public const DEFAULT_THRESHOLD_PERCENTAGE = 150.0;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::ThresholdExceeded;
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
            $thresholdPercentage = (float) ($rule->parameters()['thresholdPercentage'] ?? self::DEFAULT_THRESHOLD_PERCENTAGE);

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

                    $historical = array_filter(
                        $dailyWastes,
                        fn(DailyWaste $dw) => $dw->day()->format('Y-m-d') !== $yesterday->format('Y-m-d'),
                    );

                    $values = array_filter(
                        array_map(fn(DailyWaste $dw) => (float) $dw->load(), $historical),
                        fn(float $v) => $v > 0,
                    );

                    if (0 === count($values)) {
                        continue;
                    }

                    $mean = array_sum($values) / count($values);
                    $threshold = $mean * ($thresholdPercentage / 100);
                    $value = (float) $yesterdayEntry->load();

                    if ($value > $threshold) {
                        $alert = new Alert(id: AlertId::generate());
                        $alert->detect(
                            type: AlertType::ThresholdExceeded,
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
