<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Application\Alert\Check\DailyWasteWindowProvider;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;

class AbnormallyHighValueAlertChecker implements AlertChecker
{
    use GroupByWasteTrait;

    public const DEFAULT_DEVIATION_MULTIPLIER = 2.0;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteWindowProvider $dailyWasteWindowProvider,
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
        ExistingAlertsIndex $existingAlerts,
    ): array {
        $alerts = [];
        $previousDays = $checkDate->modify('-30 days');

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
                $recentData = $this->dailyWasteWindowProvider->windowFor(
                    accountId: $accountId,
                    propertyId: $property->id(),
                    dayFrom: $previousDays,
                    dayTo: $checkDate,
                    wasteId: $rule->wasteId(),
                );

                $byWaste = $this->groupByWaste($recentData);

                foreach ($byWaste as $wasteKey => [$wasteId, $dailyWastes]) {
                    $entryToCheck = $this->findForDay($dailyWastes, $checkDate);

                    if (null === $entryToCheck) {
                        continue;
                    }

                    $historical = array_filter(
                        $dailyWastes,
                        fn(DailyWaste $dw) => $dw->day()->format('Y-m-d') !== $checkDate->format('Y-m-d'),
                    );

                    $historicalValues = array_filter(
                        array_map(fn(DailyWaste $dw) => (float) $dw->load(), $historical),
                        fn(float $v) => $v > 0,
                    );

                    if (count($historicalValues) < 2) {
                        continue;
                    }

                    $mean = array_sum($historicalValues) / count($historicalValues);
                    $variance = array_sum(array_map(fn(float $v) => ($v - $mean) ** 2, $historicalValues)) / count($historicalValues);
                    $stddev = sqrt($variance);

                    $threshold = $mean + $multiplier * $stddev;
                    $value = (float) $entryToCheck->load();

                    if ($value <= $threshold) {
                        continue;
                    }

                    if ($existingAlerts->has(AlertType::AbnormallyHigh, $property->id(), $wasteId, $checkDate)) {
                        continue;
                    }

                    $alert = new Alert(id: AlertId::generate());
                    $alert->detect(
                        type: AlertType::AbnormallyHigh,
                        propertyId: $property->id(),
                        wasteId: $wasteId,
                        accountId: $accountId,
                        detectedAt: new \DateTimeImmutable('NOW'),
                        from: $checkDate,
                        to: $checkDate,
                        value: $value,
                        threshold: $threshold,
                    );

                    $alerts[] = $alert;

                    $existingAlerts->add(AlertType::AbnormallyHigh, $property->id(), $wasteId, $checkDate);
                }
            }
        }

        return $alerts;
    }
}
