<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDonationAgreementRepository;

class DonationAgreementRepositoryMother
{
    public static function inMemory(): InMemoryDonationAgreementRepository
    {
        return new InMemoryDonationAgreementRepository();
    }
}
