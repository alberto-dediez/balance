<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreateEmissionFactorsYearServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCopiesFactorsFromSourceYearToTargetYear(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();
        $roadTransport->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupId::default(), source: 'IPCC'));
        $repository->add($roadTransport);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($roadTransport->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025, EmissionFactorGroupId::default());
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->co2eq());
        $this->assertSame('IPCC', $copied->source());

        // The source year is preserved untouched.
        $this->assertNotNull($persisted->emissionFactorFor(2024, EmissionFactorGroupId::default()));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();
        $roadTransport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupId::default()));
        $roadTransport->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupId::default()));
        $repository->add($roadTransport);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testThrowsWhenTargetYearIsFuture(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();
        $repository->add(RoadTransportMother::random());

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(
                sourceYear: 2024,
                targetYear: 9999,
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testIsHandled(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new CreateEmissionFactorsYearRequest(sourceYear: 2024)));
    }

    private function createSUT(RoadTransportRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new RoadTransportAuthorizer());
    }
}
