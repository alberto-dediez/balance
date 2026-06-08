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

class ThresholdExceededAlertChecker implements AlertChecker
{
    use GroupByWasteTrait;

    public const DEFAULT_THRESHOLD_PERCENTAGE = 150.0;
    public const DEFAULT_STATS_DAYS = 30;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteWindowProvider $dailyWasteWindowProvider,
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
        ExistingAlertsIndex $existingAlerts,
    ): array {
        $alerts = [];

        foreach ($rules as $rule) {
            $thresholdPercentage = (float) ($rule->parameters()['thresholdPercentage'] ?? self::DEFAULT_THRESHOLD_PERCENTAGE);
            $statsDays = (int) ($rule->parameters()['statisticsDays'] ?? self::DEFAULT_STATS_DAYS);
            $previousDays = $checkDate->modify("-{$statsDays} days");

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

                if (null !== $rule->wasteId()) {
                    $ruleWasteKey = (string) $rule->wasteId();
                    $byWaste = isset($byWaste[$ruleWasteKey]) ? [$ruleWasteKey => $byWaste[$ruleWasteKey]] : [];
                }

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

                    if (0 === count($historicalValues)) {
                        continue;
                    }

                    $mean = array_sum($historicalValues) / count($historicalValues);
                    $threshold = $mean * ($thresholdPercentage / 100);
                    $value = (float) $entryToCheck->load();

                    if ($value <= $threshold) {
                        continue;
                    }

                    if ($existingAlerts->has(type: AlertType::ThresholdExceeded, propertyId: $property->id(), wasteId: $wasteId, checkDate: $checkDate, alertPriority: $rule->priority())) {
                        continue;
                    }

                    $alert = new Alert(id: AlertId::generate());
                    $alert->detect(
                        type: AlertType::ThresholdExceeded,
                        propertyId: $property->id(),
                        wasteId: $wasteId,
                        accountId: $accountId,
                        detectedAt: new \DateTimeImmutable('NOW'),
                        from: $checkDate,
                        to: $checkDate,
                        value: $value,
                        threshold: $threshold,
                        priority: $rule->priority(),
                    );

                    $alerts[] = $alert;

                    $existingAlerts->add(type: AlertType::ThresholdExceeded, propertyId: $property->id(), wasteId: $wasteId, checkDate: $checkDate, alertPriority: $rule->priority());
                }
            }
        }

        return $alerts;
    }
}
