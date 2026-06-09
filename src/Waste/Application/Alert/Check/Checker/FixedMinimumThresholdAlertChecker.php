<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;

class FixedMinimumThresholdAlertChecker implements AlertChecker
{
    public const DEFAULT_THRESHOLD_KG = 50.0;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::FixedMinimumThreshold;
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
        ExistingAlertsIndex $existingAlerts,
    ): array {
        $alerts = [];

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
                    wasteId: $rule->wasteId(),
                    accountId: $accountId,
                    dayFrom: $checkDate,
                    dayTo: $checkDate,
                    limit: 0,
                ));

                $byWaste = $this->groupByWaste($dailyWastes);

                foreach ($byWaste as [$wasteId, $entries]) {
                    $total = array_sum(array_map(fn(DailyWaste $dw) => (float) $dw->load(), $entries));

                    if ($total >= $thresholdKg) {
                        continue;
                    }

                    if ($existingAlerts->has(AlertType::FixedMinimumThreshold, $property->id(), $wasteId, $checkDate, $rule->priority())) {
                        continue;
                    }

                    $alert = new Alert(id: AlertId::generate());
                    $alert->detect(
                        type: AlertType::FixedMinimumThreshold,
                        propertyId: $property->id(),
                        wasteId: $wasteId,
                        accountId: $accountId,
                        detectedAt: new \DateTimeImmutable('NOW'),
                        from: $checkDate,
                        to: $checkDate,
                        value: $total,
                        threshold: $thresholdKg,
                        priority: $rule->priority(),
                    );

                    $alerts[] = $alert;
                    $existingAlerts->add(AlertType::FixedMinimumThreshold, $property->id(), $wasteId, $checkDate, $rule->priority());
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
