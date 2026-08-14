<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\RecordedEvent\Register;

use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventRequest;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterRecordedEventServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate()),
        );

        $performBy = UserMother::registeredUser();

        $result = $sut($request, $performBy);

        $this->assertNotEmpty((string) $result->recordedEventId);
        $this->assertEquals(36, strlen((string) $result->recordedEventId));

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
    }

    public function testRecordsTheImpersonator(): void
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $impersonator = UserMother::superAdminUser();
        $impersonated = UserMother::registeredUser();

        $request = new RegisterRecordedEventRequest(
            userId: $impersonated->id(),
            event: new UserRemoved(UserId::generate()),
            impersonatorId: $impersonator->id(),
        );

        $result = $sut($request, $impersonated);

        $this->assertEquals($impersonator->id(), $result->impersonatorId);

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
        $this->assertTrue($recordedEventPersisted->isImpersonated());
        $this->assertEquals($impersonator->id(), $recordedEventPersisted->impersonatorId());
    }

    public function testEventsAreNotImpersonatedByDefault(): void
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate()),
        );

        $result = $sut($request, UserMother::registeredUser());

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
        $this->assertFalse($recordedEventPersisted->isImpersonated());
        $this->assertNull($recordedEventPersisted->impersonatorId());
    }

    public function testNoUserCanRecordEvents(): void
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterRecordedEventRequest(
            userId: UserId::generate(),
            event: new UserRemoved(UserId::generate()),
        );

        $performBy = null;

        $result = $sut($request, $performBy);

        $this->assertNotEmpty((string) $result->recordedEventId);
        $this->assertEquals(36, strlen((string) $result->recordedEventId));

        $recordedEventPersisted = $repository->find($result->recordedEventId);

        $this->assertNotNull($recordedEventPersisted);
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new RegisterRecordedEventRequest(
            userId: $user->id(),
            event: new UserRemoved(UserId::generate()),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryRecordedEventRepository $repository): RegisterRecordedEventService
    {
        return new RegisterRecordedEventService($repository, RecordedEventAuthorizerMother::create());
    }
}
