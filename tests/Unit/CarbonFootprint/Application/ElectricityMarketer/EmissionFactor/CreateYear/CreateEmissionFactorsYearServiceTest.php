<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
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
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();
        $electricityMarketer->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, source: 'IPCC'));
        $repository->add($electricityMarketer);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($electricityMarketer->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->co2eq());
        $this->assertSame('IPCC', $copied->source());

        $this->assertNotNull($persisted->emissionFactorFor(2024));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();
        $electricityMarketer->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024));
        $electricityMarketer->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025));
        $repository->add($electricityMarketer);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testThrowsWhenTargetYearIsFuture(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $repository->add(ElectricityMarketerMother::random());

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
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new CreateEmissionFactorsYearRequest(sourceYear: 2024)));
    }

    private function createSUT(ElectricityMarketerRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new ElectricityMarketerAuthorizer());
    }
}
