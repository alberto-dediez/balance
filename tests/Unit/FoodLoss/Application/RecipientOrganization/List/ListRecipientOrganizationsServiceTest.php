<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\RecipientOrganization\List;

use App\FoodLoss\Application\RecipientOrganization\List\ListRecipientOrganizationsRequest;
use App\FoodLoss\Application\RecipientOrganization\List\ListRecipientOrganizationsService;
use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListRecipientOrganizationsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = RecipientOrganizationRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('test-account-id'));

        $accountId = 'test-account-id';

        $repository->add(RecipientOrganizationMother::from('Org 1', (string) RecipientOrganizationId::generate(), $accountId));
        $repository->add(RecipientOrganizationMother::from('Org 2', (string) RecipientOrganizationId::generate(), $accountId));
        $repository->add(RecipientOrganizationMother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListRecipientOrganizationsRequest(
            accountId: new AccountId($accountId),
        ), $user);

        $this->assertEquals(2, $result->totalResults);
        $this->assertCount(2, $result->recipientOrganizations);
    }

    private function createSUT(RecipientOrganizationRepository $repository): ListRecipientOrganizationsService
    {
        return new ListRecipientOrganizationsService(
            repository: $repository,
            authorizer: new RecipientOrganizationAuthorizer(),
        );
    }
}
