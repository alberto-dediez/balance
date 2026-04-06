<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\RecipientOrganization\Update;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Application\RecipientOrganization\Update\UpdateRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Update\UpdateRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateRecipientOrganizationServiceTest extends TestCase
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
            name: 'Original Name',
            id: (string) RecipientOrganizationId::generate(),
            accountId: 'test-account-id',
        );

        $repository->add($recipientOrganization);

        $sut = $this->createSUT($repository);

        $request = new UpdateRecipientOrganizationRequest(
            recipientOrganizationId: $recipientOrganization->id(),
            name: 'Updated Name',
            type: RecipientOrganizationType::Ngo,
        );

        $result = $sut($request, $user);

        $this->assertEquals('Updated Name', $result->name);

        $persisted = $repository->find($result->recipientOrganizationId);

        $this->assertNotNull($persisted);
        $this->assertEquals('Updated Name', $persisted->name());
    }

    public function testIsHandle(): void
    {
        $repository = RecipientOrganizationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $recipientOrganization = RecipientOrganizationMother::registeredRecipientOrganization();

        $result = $sut->isHandled(new UpdateRecipientOrganizationRequest(
            recipientOrganizationId: $recipientOrganization->id(),
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RecipientOrganizationRepository $repository): UpdateRecipientOrganizationService
    {
        return new UpdateRecipientOrganizationService(
            recipientOrganizationRepository: $repository,
            authorizer: new RecipientOrganizationAuthorizer(),
        );
    }
}
