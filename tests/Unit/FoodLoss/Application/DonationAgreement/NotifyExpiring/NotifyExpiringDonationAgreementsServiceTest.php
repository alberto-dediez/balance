<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\NotifyExpiringDonationAgreementsRequest;
use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\NotifyExpiringDonationAgreementsService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDonationAgreementRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class NotifyExpiringDonationAgreementsServiceTest extends TestCase
{
    private SpyDonationAgreementExpirationNotifier $notifier;
    private LocaleSwitcher $localeSwitcher;

    protected function setUp(): void
    {
        $this->notifier = new SpyDonationAgreementExpirationNotifier();

        $this->localeSwitcher = $this->createMock(LocaleSwitcher::class);
        $this->localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(static function (string $locale, \Closure $fn): mixed {
                return $fn();
            });
    }

    public function testNotifiesExpiringAgreements(): void
    {
        $accountId = new AccountId('account-id-test');

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(AccountMother::fromNameAndId('Test Account', 'account-id-test'));

        $donationAgreementRepository = new InMemoryDonationAgreementRepository(accountRepository: $accountRepository);
        $userRepository = new InMemoryUserRepository();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);
        $userRepository->add($owner);

        $agreement = DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: 'org-1',
            accountId: 'account-id-test',
            startDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: (string) $owner->id(),
            path: 'path/to/file.pdf',
        );
        $donationAgreementRepository->add($agreement);

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(1, $result->notified);
        $this->assertCount(1, $this->notifier->calls);
        $this->assertTrue($agreement->id()->equals($this->notifier->calls[0]['agreement']->id()));
        $this->assertNotNull($agreement->expirationWarningNotifiedAt());
    }

    public function testNotifiesEachOwnerOfTheSameAccount(): void
    {
        $accountId = new AccountId('account-id-test');

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(AccountMother::fromNameAndId('Test Account', 'account-id-test'));

        $donationAgreementRepository = new InMemoryDonationAgreementRepository(accountRepository: $accountRepository);
        $userRepository = new InMemoryUserRepository();

        $owner1 = UserMother::fromUsernameAndRoleAndAccount('owner1@test.com', Role::Owner, $accountId);
        $owner2 = UserMother::fromUsernameAndRoleAndAccount('owner2@test.com', Role::Owner, $accountId);
        $userRepository->add($owner1);
        $userRepository->add($owner2);

        $agreement = DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: 'org-1',
            accountId: 'account-id-test',
            startDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('+10 days'),
            uploadedBy: (string) $owner1->id(),
            path: 'path/to/file.pdf',
        );
        $donationAgreementRepository->add($agreement);

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(1, $result->notified);
        $this->assertCount(2, $this->notifier->calls);
    }

    public function testSkipsAlreadyNotifiedAgreements(): void
    {
        $accountId = new AccountId('account-id-test');

        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(AccountMother::fromNameAndId('Test Account', 'account-id-test'));

        $donationAgreementRepository = new InMemoryDonationAgreementRepository(accountRepository: $accountRepository);
        $userRepository = new InMemoryUserRepository();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);
        $userRepository->add($owner);

        $agreement = DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: 'org-1',
            accountId: 'account-id-test',
            startDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: (string) $owner->id(),
            path: 'path/to/file.pdf',
        );
        $agreement->markExpirationWarningNotified();
        $donationAgreementRepository->add($agreement);

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(0, $result->notified);
        $this->assertCount(0, $this->notifier->calls);
    }

    public function testSkipsAgreementsWithNoAccountId(): void
    {
        $accountRepository = new InMemoryAccountRepository();
        $donationAgreementRepository = new InMemoryDonationAgreementRepository(accountRepository: $accountRepository);
        $userRepository = new InMemoryUserRepository();

        $agreement = new \App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement(
            id: DonationAgreementId::generate(),
            expirationDate: new \DateTimeImmutable('+15 days'),
        );
        $donationAgreementRepository->add($agreement);

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(0, $result->notified);
        $this->assertCount(0, $this->notifier->calls);
    }

    public function testSkipsAgreementsWhenAccountHasNoOwners(): void
    {
        $accountRepository = new InMemoryAccountRepository();
        $accountRepository->add(AccountMother::fromNameAndId('Test Account', 'account-id-test'));

        $donationAgreementRepository = new InMemoryDonationAgreementRepository(accountRepository: $accountRepository);
        $userRepository = new InMemoryUserRepository();

        $agreement = DonationAgreementMother::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: 'org-1',
            accountId: 'account-id-test',
            startDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('+15 days'),
            uploadedBy: 'some-user-id',
            path: 'path/to/file.pdf',
        );
        $donationAgreementRepository->add($agreement);

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(1, $result->notified);
        $this->assertCount(0, $this->notifier->calls);
    }

    public function testReturnsZeroWhenNoExpiringAgreements(): void
    {
        $donationAgreementRepository = new InMemoryDonationAgreementRepository();
        $userRepository = new InMemoryUserRepository();

        $sut = $this->createSUT($donationAgreementRepository, $userRepository);

        $result = $sut(new NotifyExpiringDonationAgreementsRequest(), UserMother::systemUser());

        $this->assertEquals(0, $result->notified);
        $this->assertCount(0, $this->notifier->calls);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(new InMemoryDonationAgreementRepository(), new InMemoryUserRepository());

        $this->assertTrue($sut->isHandled(new NotifyExpiringDonationAgreementsRequest()));
    }

    private function createSUT(
        InMemoryDonationAgreementRepository $donationAgreementRepository,
        InMemoryUserRepository $userRepository,
    ): NotifyExpiringDonationAgreementsService {
        return new NotifyExpiringDonationAgreementsService(
            donationAgreementRepository: $donationAgreementRepository,
            userRepository: $userRepository,
            notifier: $this->notifier,
            localeSwitcher: $this->localeSwitcher,
        );
    }
}
