<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\RecipientOrganization\Register;

use App\FoodLoss\Application\RecipientOrganization\RecipientOrganizationAuthorizer;
use App\FoodLoss\Application\RecipientOrganization\Register\RegisterRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Register\RegisterRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRegistered;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterRecipientOrganizationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = RecipientOrganizationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $accountId = new AccountId('test-account-id');
        $request = new RegisterRecipientOrganizationRequest(
            name: 'Banco de Alimentos Test',
            accountId: $accountId,
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->recipientOrganizationId);
        $this->assertEquals(36, strlen((string) $result->recipientOrganizationId));
        $this->assertEquals('Banco de Alimentos Test', $result->name);

        $persisted = $repository->find($result->recipientOrganizationId);

        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RecipientOrganizationRegistered);
        $this->assertEquals('Banco de Alimentos Test', $lastEvent->name());
        $this->assertTrue($persisted->id()->equals($lastEvent->recipientOrganizationId()));
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(RecipientOrganizationRepositoryMother::inMemory());

        $result = $sut->isHandled(new RegisterRecipientOrganizationRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RecipientOrganizationRepository $repository): RegisterRecipientOrganizationService
    {
        return new RegisterRecipientOrganizationService(
            recipientOrganizationRepository: $repository,
            authorizer: new RecipientOrganizationAuthorizer(),
        );
    }
}
