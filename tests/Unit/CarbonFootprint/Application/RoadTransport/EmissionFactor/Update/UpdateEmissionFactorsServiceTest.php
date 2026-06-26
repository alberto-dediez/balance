<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\EmissionFactor\Update;

use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateEmissionFactorsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAddsEmissionFactorForYear(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();
        $repository->add($roadTransport);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    roadTransportId: (string) $roadTransport->id(),
                    roadTransportName: $roadTransport->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($roadTransport->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();
        $roadTransport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025));
        $repository->add($roadTransport);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    roadTransportId: (string) $roadTransport->id(),
                    roadTransportName: $roadTransport->name(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($roadTransport->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025));
    }

    public function testIsHandled(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new UpdateEmissionFactorsRequest(year: 2025)));
    }

    private function createSUT(RoadTransportRepository $repository): UpdateEmissionFactorsService
    {
        return new UpdateEmissionFactorsService($repository, new RoadTransportAuthorizer());
    }
}
