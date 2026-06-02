<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;

interface AlertChecker
{
    public function type(): AlertType;

    /** @return Alert[] */
    public function check(
        AccountId $accountId,
        AlertConfiguration $config,
        \DateTimeImmutable $checkDate,
    ): array;
}
