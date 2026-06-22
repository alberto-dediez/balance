<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\Shared\Application\CommandBus\Command;

class NotifyExpiringDonationAgreementsRequest implements Command
{
    public function __construct(
        public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
