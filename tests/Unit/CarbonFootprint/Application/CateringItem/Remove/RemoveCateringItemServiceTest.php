<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\Remove;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\Remove\RemoveCateringItemRequest;
use App\CarbonFootprint\Application\CateringItem\Remove\RemoveCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRemoved;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCateringItemServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $cateringItem = CateringItemMother::registeredCateringItem();

        $repository->add($cateringItem);

        $request = new RemoveCateringItemRequest(
            cateringItemId: $cateringItem->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($cateringItem->id()->equals($result->cateringItemId));

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNull($cateringItemPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CateringItemRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $cateringItem = CateringItemMother::random();

        $result = $sut->isHandled(new RemoveCateringItemRequest(
            cateringItemId: $cateringItem->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CateringItemRepository $repository): RemoveCateringItemService
    {
        return new RemoveCateringItemService($repository, new CateringItemAuthorizer());
    }
}
