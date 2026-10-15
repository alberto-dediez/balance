<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\Remove;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Application\PurchaseService\Remove\RemovePurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Remove\RemovePurchaseServiceService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRemoved;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemovePurchaseServiceServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $repository->add($purchaseService);

        $request = new RemovePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($purchaseService->id()->equals($result->purchaseServiceId));

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNull($purchaseServicePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PurchaseServiceRemoved);
    }

    public function testThrowsWhenNotFound(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemovePurchaseServiceRequest(purchaseServiceId: PurchaseServiceId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseService = PurchaseServiceMother::random();
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemovePurchaseServiceRequest(purchaseServiceId: $purchaseService->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $purchaseService = PurchaseServiceMother::random();

        $result = $sut->isHandled(new RemovePurchaseServiceRequest(
            purchaseServiceId: $purchaseService->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PurchaseServiceRepository $repository): RemovePurchaseServiceService
    {
        return new RemovePurchaseServiceService($repository, new PurchaseServiceAuthorizer());
    }
}
