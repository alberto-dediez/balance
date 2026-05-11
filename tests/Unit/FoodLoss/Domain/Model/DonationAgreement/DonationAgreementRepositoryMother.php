<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDonationAgreementRepository;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;

class DonationAgreementRepositoryMother
{
    public static function inMemory(?AccountRepository $accountRepository = null): InMemoryDonationAgreementRepository
    {
        return new InMemoryDonationAgreementRepository(accountRepository: $accountRepository ?? new InMemoryAccountRepository());
    }
}
