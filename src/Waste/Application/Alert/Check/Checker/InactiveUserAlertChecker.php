<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class InactiveUserAlertChecker implements AlertChecker
{
    public const DEFAULT_INACTIVE_DAYS = 7;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function type(): AlertType
    {
        return AlertType::InactiveUser;
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
        $inactiveDays = (int) ($rules[0]->parameters()['inactiveDays'] ?? self::DEFAULT_INACTIVE_DAYS);
        $periodFrom = $checkDate->modify(sprintf('-%d days', $inactiveDays));

        if ($existingAlerts->has(AlertType::InactiveUser, null, null)) {
            return $alerts;
        }

        $users = $this->userRepository->findByAccountId($accountId);
        $hasInactiveUser = false;

        foreach ($users as $user) {
            $measurements = $this->measurementRepository->findByFilter(new MeasurementFilter(
                accountId: $accountId,
                userId: $user->id(),
                registeredAtFrom: $periodFrom,
                registeredAtTo: $checkDate,
                limit: 1,
            ));

            if (0 === count($measurements)) {
                $hasInactiveUser = true;

                break;
            }
        }

        if ($hasInactiveUser) {
            $alert = new Alert(id: AlertId::generate());
            $alert->detect(
                type: AlertType::InactiveUser,
                propertyId: null,
                wasteId: null,
                accountId: $accountId,
                detectedAt: new \DateTimeImmutable('NOW'),
                from: $periodFrom,
                to: $checkDate,
                value: null,
                threshold: null,
            );

            $alerts[] = $alert;
            $existingAlerts->add(AlertType::InactiveUser, null, null);
        }

        return $alerts;
    }
}
