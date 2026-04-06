<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineDonationAgreementRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDonationAgreementRepositoryTest extends DonationAgreementRepositoryTest
{
    protected function createSUT(): DonationAgreementRepository
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDonationAgreementRepository($manager);
    }
}
