<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;

class InMemoryDonationAgreementRepositoryTest extends DonationAgreementRepositoryTest
{
    protected function createSUT(): DonationAgreementRepository
    {
        return DonationAgreementRepositoryMother::inMemory();
    }

    public function testFindExpiringSoonOnlyReturnsAgreementsWithActiveAccount(): void
    {
        $activeAccountId = new AccountId('active-account');
        $inactiveAccountId = new AccountId('inactive-account');

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(new Account(id: $activeAccountId, name: 'Active', active: true));
        $accountRepository->add(new Account(id: $inactiveAccountId, name: 'Inactive', active: false));

        $repository = DonationAgreementRepositoryMother::inMemory($accountRepository);

        $agreementActive = DonationAgreementMother::from(
            id: 'agreement-active',
            recipientOrganizationId: 'org-1',
            accountId: (string) $activeAccountId,
            startDate: new \DateTimeImmutable('-1 month'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: 'uploader-1',
            path: 'path/active.pdf',
        );

        $agreementInactive = DonationAgreementMother::from(
            id: 'agreement-inactive',
            recipientOrganizationId: 'org-2',
            accountId: (string) $inactiveAccountId,
            startDate: new \DateTimeImmutable('-1 month'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: 'uploader-2',
            path: 'path/inactive.pdf',
        );

        $repository->add($agreementActive);
        $repository->add($agreementInactive);

        $results = $repository->findExpiringSoon(30);

        $this->assertCount(1, $results);
        $this->assertEquals($agreementActive->id(), $results[0]->id());
    }
}
