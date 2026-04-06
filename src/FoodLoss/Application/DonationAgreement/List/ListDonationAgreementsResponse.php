<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\List;

class ListDonationAgreementsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement> $donationAgreements */
    public function __construct(public array $donationAgreements = [], public int $totalResults = 0) {}
}
