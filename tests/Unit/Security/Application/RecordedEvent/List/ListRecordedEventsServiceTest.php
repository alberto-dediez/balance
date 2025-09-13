<?php

namespace App\Tests\Unit\Security\Application\RecordedEvent\List;

use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsResponse;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListRecordedEventsServiceTest extends TestCase
{
    protected function setUp(): void
    {

    }

    public function testInvoke()
    {
        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());

        $performBy = UserMother::adminUser();

        $request = new ListRecordedEventsRequest();

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListRecordedEventsResponse);
        $this->assertCount(4, $result->events);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryRecordedEventRepository();

        $sut = $this->createSUT($repository);

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());

        $performBy = UserMother::ownerUser();

        $request = new ListRecordedEventsRequest();

        $result = $sut($request, $performBy);
    }

    private function createSUT($repository)
    {
        return new ListRecordedEventsService($repository, RecordedEventAuthorizerMother::create());
    }
}
