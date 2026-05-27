<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DeliveryLog\Register;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRegistered;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterDeliveryLogServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId);

        $request = new RegisterDeliveryLogRequest(
            date: new \DateTimeImmutable('2026-04-06'),
            quantity: 15.5,
            unit: DeliveryLogUnit::Rations,
            productType: FoodType::Processed,
            batch: 'LOT-99',
            recipientOrganizationId: new RecipientOrganizationId('recipient-id'),
            propertyId: new PropertyId('property-id'),
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->deliveryLogId);
        $this->assertEquals(FoodType::Processed, $result->productType);

        $deliveryLogPersisted = $repository->find($result->deliveryLogId);

        $this->assertNotNull($deliveryLogPersisted);
        $this->assertEquals(15.5, $deliveryLogPersisted->quantity());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(DeliveryLogRegistered::class, $lastEvent);
    }

    private function createSUT(DeliveryLogRepository $repository): RegisterDeliveryLogService
    {
        return new RegisterDeliveryLogService(
            repository: $repository,
            authorizer: new DeliveryLogAuthorizer(),
        );
    }
}
