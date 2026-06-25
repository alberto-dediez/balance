<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\Remove;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Application\MerchandisingItem\Remove\RemoveMerchandisingItemRequest;
use App\CarbonFootprint\Application\MerchandisingItem\Remove\RemoveMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRemoved;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMerchandisingItemServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();

        $repository->add($merchandisingItem);

        $request = new RemoveMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($merchandisingItem->id()->equals($result->merchandisingItemId));

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNull($merchandisingItemPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MerchandisingItemRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $merchandisingItem = MerchandisingItemMother::random();

        $result = $sut->isHandled(new RemoveMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MerchandisingItemRepository $repository): RemoveMerchandisingItemService
    {
        return new RemoveMerchandisingItemService($repository, new MerchandisingItemAuthorizer());
    }
}
