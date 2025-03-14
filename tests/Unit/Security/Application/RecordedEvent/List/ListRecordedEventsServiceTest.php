<?php

namespace App\Tests\Unit\Security\Application\RecordedEvent\List;

use PHPUnit\Framework\TestCase;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsResponse;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
use App\Security\Infrastructure\Repository\RecordedEvent\InMemoryRecordedEventRepository;

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

        $request = new ListRecordedEventsRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListRecordedEventsResponse);
        $this->assertCount(4, $result->events);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListRecordedEventsService($repository);
    }
}