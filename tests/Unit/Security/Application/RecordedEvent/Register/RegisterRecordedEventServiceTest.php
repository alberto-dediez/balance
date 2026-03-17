<?php

namespace App\Tests\Unit\Security\Application\RecordedEvent\Register;

use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventRequest;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventResponse;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterRecordedEventServiceTest extends TestCase
{
    public function testUseCase()
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate())
        );

        $performBy = UserMother::registeredUser();

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertNotEmpty((string) $result->recordedEventId);
        $this->assertEquals(36, strlen((string) $result->recordedEventId));

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
    }

    public function testNoUserCanRecordEvents()
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate())
        );

        $performBy = null;

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertNotEmpty((string) $result->recordedEventId);
        $this->assertEquals(36, strlen((string) $result->recordedEventId));

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new RegisterRecordedEventRequest(
            userId: $user->id(),
            event: new UserRemoved(UserId::generate())
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new RegisterRecordedEventService($repository, RecordedEventAuthorizerMother::create());
    }
}
