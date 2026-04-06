<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Remove;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Shared\Application\CommandBus\Command;

class RemoveDonationAgreementRequest implements Command
{
    public function __construct(public DonationAgreementId $donationAgreementId) {}
}
