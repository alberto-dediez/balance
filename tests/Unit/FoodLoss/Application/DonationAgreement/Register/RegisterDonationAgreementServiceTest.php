<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DonationAgreement\Register;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementUploader;
use App\FoodLoss\Application\DonationAgreement\Register\RegisterDonationAgreementRequest;
use App\FoodLoss\Application\DonationAgreement\Register\RegisterDonationAgreementService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRegistered;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterDonationAgreementServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = DonationAgreementRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $recipientOrganizationId = RecipientOrganizationId::generate();

        $request = new RegisterDonationAgreementRequest(
            recipientOrganizationId: $recipientOrganizationId,
            accountId: new AccountId('account-test'),
            startDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2024-01-01'),
            uploadedBy: $user->id(),
            temporaryFilePath: '/tmp/test.pdf',
            fileExtension: 'pdf',
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->donationAgreementId);
        $this->assertStringStartsWith('donation_agreements/account-test/', $result->path);
        $this->assertStringEndsWith('.pdf', $result->path);

        $persisted = $repository->find($result->donationAgreementId);
        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertTrue($lastEvent instanceof DonationAgreementRegistered);
        $this->assertStringStartsWith('donation_agreements/account-test/', $lastEvent->path());
    }

    private function createSUT(DonationAgreementRepository $repository): RegisterDonationAgreementService
    {
        $fileUploader = new class implements DonationAgreementUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId, RecipientOrganizationId $recipientOrganizationId): string
            {
                return 'donation_agreements/' . $accountId . '/test.' . $extension;
            }
        };

        return new RegisterDonationAgreementService(
            donationAgreementRepository: $repository,
            authorizer: new DonationAgreementAuthorizer(),
            fileUploader: $fileUploader,
        );
    }
}
