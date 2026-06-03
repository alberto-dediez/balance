<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class UnusedContainerAlertChecker implements AlertChecker
{
    public const DEFAULT_UNUSED_DAYS = 7;

    public function __construct(
        private readonly ContainerRepository $containerRepository,
        private readonly MeasurementRepository $measurementRepository,
        private readonly AlertRepository $alertRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::UnusedContainer;
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
        $alertedKeys = [];

        foreach ($rules as $rule) {
            $unusedDays = (int) ($rule->parameters()['unusedDays'] ?? self::DEFAULT_UNUSED_DAYS);
            $periodFrom = $checkDate->modify(sprintf('-%d days', $unusedDays));

            $containerFilter = new ContainerFilter(
                accountId: $accountId,
                propertyId: $rule->propertyId(),
                wasteId: $rule->wasteId(),
                limit: 0,
            );

            $containers = $this->containerRepository->findByFilter($containerFilter);

            if (0 === count($containers)) {
                continue;
            }
            foreach ($containers as $container) {
                $propertyId = $container->propertyId();
                $wasteId = $container->wasteId();

                if (null === $propertyId || null === $wasteId) {
                    continue;
                }

                $alertKey = (string) $propertyId . '|' . (string) $wasteId;

                if (isset($alertedKeys[$alertKey])) {
                    continue;
                }

                $measurements = $this->measurementRepository->findByFilter(new MeasurementFilter(
                    containerId: $container->id(),
                    accountId: $accountId,
                    registeredAtFrom: $periodFrom,
                    registeredAtTo: $checkDate,
                    limit: 1,
                ));

                if (count($measurements) > 0) {
                    continue;
                }

                $existing = $this->alertRepository->findByFilter(new AlertFilter(
                    accountId: $accountId,
                    propertyId: $propertyId,
                    wasteId: $wasteId,
                    type: AlertType::UnusedContainer,
                    status: AlertStatus::New,
                    limit: 1,
                ));

                if (count($existing) > 0) {
                    $alertedKeys[$alertKey] = true;

                    continue;
                }

                $alert = new Alert(id: AlertId::generate());
                $alert->detect(
                    type: AlertType::UnusedContainer,
                    propertyId: $propertyId,
                    wasteId: $wasteId,
                    accountId: $accountId,
                    detectedAt: $checkDate,
                    from: $periodFrom,
                    to: $checkDate,
                    value: null,
                    threshold: null,
                );

                $alerts[] = $alert;
                $alertedKeys[$alertKey] = true;
            }
        }

        return $alerts;
    }
}
