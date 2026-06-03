<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;

class FixedThresholdAlertChecker implements AlertChecker
{
    public const DEFAULT_THRESHOLD_KG = 500.0;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
        private readonly AlertRepository $alertRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::FixedThreshold;
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

        foreach ($rules as $rule) {
            $thresholdKg = (float) ($rule->parameters()['thresholdKg'] ?? self::DEFAULT_THRESHOLD_KG);

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
                $dailyWastes = $this->dailyWasteRepository->findByFilter(new DailyWasteFilter(
                    properties: [$property->id()],
                    accountId: $accountId,
                    dayFrom: $checkDate->modify('-2 days'),
                    dayTo: $checkDate,
                    limit: 0,
                ));

                $yesterdayWastes = array_filter(
                    $dailyWastes,
                    fn(DailyWaste $dw) => $dw->day()->format('Y-m-d') === $yesterday->format('Y-m-d'),
                );

                $byWaste = $this->groupByWaste($yesterdayWastes);

                if (null !== $rule->wasteId()) {
                    $ruleWasteKey = (string) $rule->wasteId();
                    $byWaste = isset($byWaste[$ruleWasteKey]) ? [$ruleWasteKey => $byWaste[$ruleWasteKey]] : [];
                }

                foreach ($byWaste as [$wasteId, $entries]) {
                    $total = array_sum(array_map(fn(DailyWaste $dw) => (float) $dw->load(), $entries));

                    if ($total <= $thresholdKg) {
                        continue;
                    }

                    $existing = $this->alertRepository->findByFilter(new AlertFilter(
                        accountId: $accountId,
                        propertyId: $property->id(),
                        wasteId: $wasteId,
                        type: AlertType::FixedThreshold,
                        status: AlertStatus::New,
                        limit: 1,
                    ));

                    if (count($existing) > 0) {
                        continue;
                    }

                    $alert = new Alert(id: AlertId::generate());
                    $alert->detect(
                        type: AlertType::FixedThreshold,
                        propertyId: $property->id(),
                        wasteId: $wasteId,
                        accountId: $accountId,
                        detectedAt: $checkDate,
                        from: $yesterday,
                        to: $yesterday,
                        value: $total,
                        threshold: $thresholdKg,
                    );

                    $alerts[] = $alert;
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
}
