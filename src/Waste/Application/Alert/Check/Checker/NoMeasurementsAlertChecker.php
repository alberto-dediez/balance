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
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;

class NoMeasurementsAlertChecker implements AlertChecker
{
    public const DEFAULT_NO_MEASUREMENTS_DAYS = 7;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::NoMeasurements;
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
            $days = (int) ($rule->parameters()['noMeasurementsDays'] ?? self::DEFAULT_NO_MEASUREMENTS_DAYS);

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

            $periodFrom = $checkDate->modify(sprintf('-%d days', $days));
            $periodTo = $checkDate;

            foreach ($properties as $property) {
                $dailyWastes = $this->dailyWasteRepository->findByFilter(new DailyWasteFilter(
                    properties: [$property->id()],
                    wasteId: $rule->wasteId(),
                    accountId: $accountId,
                    dayFrom: $periodFrom,
                    dayTo: $periodTo,
                    limit: 0,
                ));

                if (0 === count($dailyWastes)) {
                    if ($existingAlerts->has(type: AlertType::NoMeasurements, propertyId: $property->id(), wasteId: $rule->wasteId(), checkDate: null, alertPriority: $rule->priority())) {
                        continue;
                    }

                    $alert = new Alert(id: AlertId::generate());
                    $alert->detect(
                        type: AlertType::NoMeasurements,
                        propertyId: $property->id(),
                        wasteId: $rule->wasteId(),
                        accountId: $accountId,
                        detectedAt: new \DateTimeImmutable('NOW'),
                        from: $periodFrom,
                        to: $periodTo,
                        value: null,
                        threshold: null,
                        priority: $rule->priority(),
                    );

                    $alerts[] = $alert;

                    $existingAlerts->add(type: AlertType::NoMeasurements, propertyId: $property->id(), wasteId: $rule->wasteId(), checkDate: null, alertPriority: $rule->priority());
                }
            }
        }

        return $alerts;
    }
}
