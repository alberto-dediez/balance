<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\NotifyExpiringDonationAgreementsService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementExpirationWarningNotified;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class NotifyExpiringDonationAgreementsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testNotifiesOwnersAndMarksAgreementAsNotified(): void
    {
        $accountId = new AccountId('account-test');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $agreement = DonationAgreementMother::from(
            id: 'agreement-1',
            recipientOrganizationId: 'org-1',
            accountId: (string) $accountId,
            startDate: new \DateTimeImmutable('-1 month'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: (string) $owner->id(),
            path: 'path/file.pdf',
        );

        $repository = DonationAgreementRepositoryMother::inMemory();
        $repository->add($agreement);

        $spyNotifier = new SpyDonationAgreementExpirationNotifier();
        $sut = new NotifyExpiringDonationAgreementsService($repository, new InMemoryUserRepository([$owner]), $spyNotifier);

        $sut($agreement);

        $this->assertCount(1, $spyNotifier->calls);
        $this->assertSame($agreement, $spyNotifier->calls[0]['agreement']);
        $this->assertSame($owner, $spyNotifier->calls[0]['owner']);

        $persisted = $repository->find($agreement->id());
        $this->assertNotNull($persisted);
        $this->assertNotNull($persisted->expirationWarningNotifiedAt());

        $this->assertInstanceOf(DonationAgreementExpirationWarningNotified::class, $this->spySubscriber->lastEvent());
    }

    public function testDoesNothingWhenAgreementHasNoAccount(): void
    {
        $agreement = new DonationAgreement(id: DonationAgreementId::generate());

        $repository = DonationAgreementRepositoryMother::inMemory();
        $repository->add($agreement);

        $spyNotifier = new SpyDonationAgreementExpirationNotifier();
        $sut = new NotifyExpiringDonationAgreementsService($repository, new InMemoryUserRepository(), $spyNotifier);

        $sut($agreement);

        $this->assertCount(0, $spyNotifier->calls);
        $this->assertNull($agreement->expirationWarningNotifiedAt());
    }

    public function testDoesNothingWhenAccountHasNoOwners(): void
    {
        $accountId = new AccountId('account-no-owners');
        $user = UserMother::fromUsernameAndRoleAndAccount('user@test.com', Role::User, $accountId);

        $agreement = DonationAgreementMother::from(
            id: 'agreement-no-owner',
            recipientOrganizationId: 'org-1',
            accountId: (string) $accountId,
            startDate: new \DateTimeImmutable('-1 month'),
            expirationDate: new \DateTimeImmutable('+10 days'),
            uploadedBy: (string) $user->id(),
            path: 'path/file.pdf',
        );

        $spyNotifier = new SpyDonationAgreementExpirationNotifier();
        $sut = new NotifyExpiringDonationAgreementsService(
            DonationAgreementRepositoryMother::inMemory(),
            new InMemoryUserRepository([$user]),
            $spyNotifier,
        );

        $sut($agreement);

        $this->assertCount(0, $spyNotifier->calls);
        $this->assertNull($agreement->expirationWarningNotifiedAt());
    }

    public function testNotifiesMultipleOwnersForSameAgreement(): void
    {
        $accountId = new AccountId('account-multi-owner');
        $owner1 = UserMother::fromUsernameAndRoleAndAccount('owner1@test.com', Role::Owner, $accountId);
        $owner2 = UserMother::fromUsernameAndRoleAndAccount('owner2@test.com', Role::Owner, $accountId);

        $agreement = DonationAgreementMother::from(
            id: 'agreement-multi',
            recipientOrganizationId: 'org-1',
            accountId: (string) $accountId,
            startDate: new \DateTimeImmutable('-1 month'),
            expirationDate: new \DateTimeImmutable('+5 days'),
            uploadedBy: (string) $owner1->id(),
            path: 'path/file.pdf',
        );

        $repository = DonationAgreementRepositoryMother::inMemory();
        $repository->add($agreement);

        $spyNotifier = new SpyDonationAgreementExpirationNotifier();
        $sut = new NotifyExpiringDonationAgreementsService($repository, new InMemoryUserRepository([$owner1, $owner2]), $spyNotifier);

        $sut($agreement);

        $this->assertCount(2, $spyNotifier->calls);
        $this->assertNotNull($agreement->expirationWarningNotifiedAt());
    }
}
