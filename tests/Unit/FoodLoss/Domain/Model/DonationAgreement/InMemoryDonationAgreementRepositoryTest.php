<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;

class InMemoryDonationAgreementRepositoryTest extends DonationAgreementRepositoryTest
{
    protected function createSUT(): DonationAgreementRepository
    {
        return DonationAgreementRepositoryMother::inMemory();
    }
}
