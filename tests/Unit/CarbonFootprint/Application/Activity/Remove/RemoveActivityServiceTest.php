<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Remove;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityRequest;
use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityService;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivityRemoved;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\WasteActivityMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemoveMaterialActivity(): void
    {
        $activity = MaterialActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut = new RemoveActivityService($repository, new ActivityAuthorizer());

        $sut(new RemoveActivityRequest(activityId: $activity->id()), UserMother::adminUser());

        $this->assertNull($repository->findById($activity->id()));
        $this->assertInstanceOf(MaterialActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRemoveWasteActivity(): void
    {
        $activity = WasteActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut = new RemoveActivityService($repository, new ActivityAuthorizer());

        $sut(new RemoveActivityRequest(activityId: $activity->id()), UserMother::adminUser());

        $this->assertNull($repository->findById($activity->id()));
        $this->assertInstanceOf(WasteActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = MaterialActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $sut = new RemoveActivityService($repository, new ActivityAuthorizer());
        $this->expectException(\Throwable::class);

        $sut(new RemoveActivityRequest(activityId: $activity->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = new RemoveActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer());
        $this->assertTrue($sut->isHandled(new RemoveActivityRequest(
            activityId: \App\CarbonFootprint\Domain\Model\Activity\ActivityId::generate(),
        )));
    }
}
