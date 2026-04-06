<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\RecipientOrganization\Remove;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Application\RecipientOrganization\Remove\RemoveRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Remove\RemoveRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemoveRecipientOrganizationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = RecipientOrganizationRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('test-account-id'));

        $recipientOrganization = RecipientOrganizationMother::from(
            name: 'To Remove',
            id: (string) RecipientOrganizationId::generate(),
            accountId: 'test-account-id',
        );

        $repository->add($recipientOrganization);

        $sut = $this->createSUT($repository);

        $result = $sut(new RemoveRecipientOrganizationRequest($recipientOrganization->id()), $user);

        $this->assertTrue($recipientOrganization->id()->equals($result->recipientOrganizationId));

        $this->assertNull($repository->find($recipientOrganization->id()));
    }

    public function testIsHandle(): void
    {
        $repository = RecipientOrganizationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $recipientOrganization = RecipientOrganizationMother::registeredRecipientOrganization();

        $result = $sut->isHandled(new RemoveRecipientOrganizationRequest(
            recipientOrganizationId: $recipientOrganization->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RecipientOrganizationRepository $repository): RemoveRecipientOrganizationService
    {
        return new RemoveRecipientOrganizationService(
            repository: $repository,
            authorizer: new RecipientOrganizationAuthorizer(),
        );
    }
}
