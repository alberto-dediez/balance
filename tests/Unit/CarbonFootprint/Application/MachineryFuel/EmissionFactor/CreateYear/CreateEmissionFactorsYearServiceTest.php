<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
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
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();
        $machineryFuel->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id(), source: 'IPCC'));
        $repository->add($machineryFuel);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($machineryFuel->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->co2eq());
        $this->assertSame('IPCC', $copied->source());

        $this->assertNotNull($persisted->emissionFactorFor(2024));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();
        $machineryFuel->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $machineryFuel->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($machineryFuel);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    private function createSUT(MachineryFuelRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new MachineryFuelAuthorizer());
    }
}
