<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\List;

use App\CarbonFootprint\Application\Transport\List\ListTransportsRequest;
use App\CarbonFootprint\Application\Transport\List\ListTransportsService;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportMode;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListTransportsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());

        $request = new ListTransportsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->transports);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(TransportMother::fromName('name-1'));
        $repository->add(TransportMother::fromName('name-2'));
        $repository->add(TransportMother::fromName('name-2'));
        $repository->add(TransportMother::fromName('name-2'));

        $request = new ListTransportsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->transports);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListTransportsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->transports);
        $this->assertEquals(3, $result->totalResults);
    }

    public function testFilterByMode(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(TransportMother::freightTrainInTonneKilometre('train', (string) TransportId::generate()));
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());

        $result = $sut(new ListTransportsRequest(mode: TransportMode::Rail), UserMother::adminUser());

        $this->assertCount(1, $result->transports);
        $this->assertEquals(1, $result->totalResults);

        $result = $sut(new ListTransportsRequest(mode: TransportMode::Road), UserMother::adminUser());

        $this->assertCount(2, $result->transports);
        $this->assertEquals(2, $result->totalResults);
    }

    private function createSUT(TransportRepository $repository): ListTransportsService
    {
        return new ListTransportsService($repository, new TransportAuthorizer());
    }
}
