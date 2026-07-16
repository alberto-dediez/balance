<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Remove;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Remove\RemoveEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Remove\RemoveEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEventCarbonFootprintServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount(
            (string) EventId::generate(),
            'account-id',
        );
        $repository->add($eventCarbonFootprint);

        $sut = $this->createSUT($repository);

        $sut(
            new RemoveEventCarbonFootprintRequest(eventCarbonFootprintId: $eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertTrue($eventCarbonFootprint->isRemoved());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventCarbonFootprintRemoved);
        $this->assertTrue($eventCarbonFootprint->id()->equals($lastEvent->eventCarbonFootprintId()));
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new RemoveEventCarbonFootprintRequest(eventCarbonFootprintId: EventCarbonFootprintMother::random()->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );
    }

    public function testIsHandle(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $eventCarbonFootprint = EventCarbonFootprintMother::random();
        $repository->add($eventCarbonFootprint);

        $result = $sut->isHandled(new RemoveEventCarbonFootprintRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EventCarbonFootprintRepository $repository): RemoveEventCarbonFootprintService
    {
        return new RemoveEventCarbonFootprintService(
            eventCarbonFootprintRepository: $repository,
            authorizer: new EventCarbonFootprintAuthorizer(),
        );
    }
}
