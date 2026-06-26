<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\EmissionFactor\List;

use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmissionFactorsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testResolvesLatestYearWhenNoYearRequested(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();
        $roadTransport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022));
        $roadTransport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024));
        $repository->add($roadTransport);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->roadTransports);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();
        $repository->add(RoadTransportMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();
        $repository->add(RoadTransportMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(RoadTransportRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new RoadTransportAuthorizer());
    }
}
