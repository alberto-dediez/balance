<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\RecordedEvent\List;

use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListRecordedEventsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
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

        $this->assertCount(4, $result->events);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess(): void
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

        $sut($request, $performBy);
    }

    private function createSUT(InMemoryRecordedEventRepository $repository): ListRecordedEventsService
    {
        return new ListRecordedEventsService($repository, RecordedEventAuthorizerMother::create());
    }
}
