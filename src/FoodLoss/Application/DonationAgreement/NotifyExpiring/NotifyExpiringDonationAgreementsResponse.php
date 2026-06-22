<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\Shared\Application\CommandBus\CommandResponse;

class NotifyExpiringDonationAgreementsResponse implements CommandResponse
{
    public function __construct(
        public readonly int $notified,
    ) {}
}
