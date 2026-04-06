<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Update;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateDonationAgreementResponse implements CommandResponse
{
    public function __construct(
        public DonationAgreementId $donationAgreementId,
        public string $path,
    ) {}
}
