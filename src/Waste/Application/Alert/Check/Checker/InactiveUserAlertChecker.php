<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserRepository;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class InactiveUserAlertChecker implements AlertChecker
{
    public const DEFAULT_INACTIVE_DAYS = 7;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MeasurementRepository $measurementRepository,
        private readonly AlertRepository $alertRepository,
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
    ): array {
        $alerts = [];
        $inactiveDays = (int) ($rules[0]->parameters()['inactiveDays'] ?? self::DEFAULT_INACTIVE_DAYS);
        $periodFrom = $checkDate->modify(sprintf('-%d days', $inactiveDays));

        $existing = $this->alertRepository->findByFilter(new AlertFilter(
            accountId: $accountId,
            type: AlertType::InactiveUser,
            status: AlertStatus::New,
            limit: 1,
        ));

        if (count($existing) > 0) {
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
                detectedAt: $checkDate,
                from: $periodFrom,
                to: $checkDate,
                value: null,
                threshold: null,
            );

            $alerts[] = $alert;
        }

        return $alerts;
    }
}
