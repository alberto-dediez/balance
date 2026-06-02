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
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;

class NoMeasurementsAlertChecker implements AlertChecker
{
    public const DEFAULT_NO_MEASUREMENTS_DAYS = 7;

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly DailyWasteRepository $dailyWasteRepository,
        private readonly AlertRepository $alertRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::NoMeasurements;
    }

    /** @return Alert[] */
    public function check(
        AccountId $accountId,
        AlertConfiguration $config,
        \DateTimeImmutable $checkDate,
    ): array {
        $alerts = [];
        $days = (int) ($config->items()->getByType(AlertType::NoMeasurements)?->parameters()['noMeasurementsDays'] ?? self::DEFAULT_NO_MEASUREMENTS_DAYS);

        $properties = $this->propertyRepository->findByFilter(new PropertyFilter(
            accountId: $accountId,
            limit: 0,
        ));

        $periodFrom = $checkDate->modify(sprintf('-%d days', $days));
        $periodTo = $checkDate;

        foreach ($properties as $property) {
            $dailyWastes = $this->dailyWasteRepository->findByFilter(new DailyWasteFilter(
                properties: [$property->id()],
                accountId: $accountId,
                dayFrom: $periodFrom,
                dayTo: $periodTo,
                limit: 0,
            ));

            if (0 === count($dailyWastes)) {
                $existing = $this->alertRepository->findByFilter(new AlertFilter(
                    accountId: $accountId,
                    propertyId: $property->id(),
                    type: AlertType::NoMeasurements,
                    status: AlertStatus::New,
                    limit: 1,
                ));

                if (count($existing) > 0) {
                    continue;
                }

                $alert = new Alert(id: AlertId::generate());
                $alert->detect(
                    type: AlertType::NoMeasurements,
                    propertyId: $property->id(),
                    wasteId: null,
                    accountId: $accountId,
                    detectedAt: $checkDate,
                    from: $periodFrom,
                    to: $periodTo,
                    value: null,
                    threshold: null,
                );

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }
}
