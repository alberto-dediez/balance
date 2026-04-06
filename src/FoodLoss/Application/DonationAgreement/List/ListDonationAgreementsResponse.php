<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\List;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;

class ListDonationAgreementsResponse
{
    /** @param array<DonationAgreement> $donationAgreements */
    public function __construct(public array $donationAgreements = [], public int $totalResults = 0) {}
}
