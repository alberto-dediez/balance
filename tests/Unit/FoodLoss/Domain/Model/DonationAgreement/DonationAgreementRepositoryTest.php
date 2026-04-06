<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementFilter;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DonationAgreementRepositoryTest extends KernelTestCase
{
    public function testAddDonationAgreementToRepository(): void
    {
        $repository = $this->createSUT();

        $donationAgreement = DonationAgreementMother::registeredDonationAgreement();

        $this->assertNull($repository->find($donationAgreement->id()));

        $repository->add($donationAgreement);

        $recoverDonationAgreement = $repository->find($donationAgreement->id());

        $this->assertNotNull($recoverDonationAgreement);
        $this->assertEquals($donationAgreement->id(), $recoverDonationAgreement->id());
    }

    public function testRemoveDonationAgreementToRepository(): void
    {
        $repository = $this->createSUT();

        $donationAgreement = DonationAgreementMother::registeredDonationAgreement();

        $repository->add($donationAgreement);

        $recoverDonationAgreement = $repository->find($donationAgreement->id());

        $this->assertNotNull($recoverDonationAgreement);
        $this->assertEquals($donationAgreement->id(), $recoverDonationAgreement->id());

        $repository->remove($donationAgreement);

        $this->assertNull($repository->find($donationAgreement->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(DonationAgreementMother::random());
        $repository->add(DonationAgreementMother::random());
        $repository->add(DonationAgreementMother::random());

        $target = DonationAgreementMother::forAccount('target-account');
        $repository->add($target);

        $filter = new DonationAgreementFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);

        $filter = new DonationAgreementFilter(
            accountId: $target->accountId(),
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals($target->id(), $result[0]->id());
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $donationAgreement = DonationAgreementMother::registeredDonationAgreement();

        $repository->add($donationAgreement);

        $recoverDonationAgreement = $repository->find($donationAgreement->id());

        $this->assertNotNull($recoverDonationAgreement);

        $recoverDonationAgreement->remove();

        $repository->add($donationAgreement);

        $this->assertNull($repository->find($donationAgreement->id()));
    }

    abstract protected function createSUT(): DonationAgreementRepository;
}
