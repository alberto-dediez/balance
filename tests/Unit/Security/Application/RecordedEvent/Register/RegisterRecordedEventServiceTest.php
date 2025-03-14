<?php

namespace App\Tests\Unit\Security\Application\RecordedEvent\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRegistered;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventRequest;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventResponse;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Repository\RecordedEvent\InMemoryRecordedEventRepository;

class RegisterRecordedEventServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate())
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterRecordedEventResponse);
        $this->assertNotEmpty((string) $result->recordedEventId);
        $this->assertEquals(36, strlen((string) $result->recordedEventId));

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
        $this->assertTrue($recordedEventPersisted instanceof RecordedEvent);
    }

    private function createSUT($repository)
    {
        return new RegisterRecordedEventService($repository);
    }
}