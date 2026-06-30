<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Register\RegisterEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Register\RegisterEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRegistered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterEventCarbonFootprintServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $eventId = EventId::generate();
        $accountId = new AccountId('account-id');

        $request = new RegisterEventCarbonFootprintRequest(
            eventId: $eventId,
            accountId: $accountId,
            calculationPeriodStartDate: new \DateTimeImmutable('2026-01-01'),
            calculationPeriodEndDate: new \DateTimeImmutable('2026-01-31'),
        );

        $result = $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId));

        $this->assertNotEmpty((string) $result->eventCarbonFootprintId);
        $this->assertEquals(36, strlen((string) $result->eventCarbonFootprintId));
        $this->assertTrue($result->eventId->equals($eventId));

        $eventCarbonFootprintPersisted = $repository->find($result->eventCarbonFootprintId);

        $this->assertNotNull($eventCarbonFootprintPersisted);

        $persistedEventId = $eventCarbonFootprintPersisted->eventId();
        $persistedAccountId = $eventCarbonFootprintPersisted->accountId();

        self::assertNotNull($persistedEventId);
        self::assertNotNull($persistedAccountId);
        $this->assertTrue($persistedEventId->equals($eventId));
        $this->assertTrue($persistedAccountId->equals($accountId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventCarbonFootprintRegistered);
        $this->assertTrue($lastEvent->eventId()->equals($eventId));
        $this->assertTrue($eventCarbonFootprintPersisted->id()->equals($lastEvent->eventCarbonFootprintId()));
    }

    public function testIsHandle(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterEventCarbonFootprintRequest(
            eventId: EventId::generate(),
            accountId: new AccountId('account-id'),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EventCarbonFootprintRepository $repository): RegisterEventCarbonFootprintService
    {
        return new RegisterEventCarbonFootprintService(
            eventCarbonFootprintRepository: $repository,
            authorizer: new EventCarbonFootprintAuthorizer(),
        );
    }
}
