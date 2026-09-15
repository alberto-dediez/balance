<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
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
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::random();
        $electricityMix->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), source: 'IPCC'));
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($electricityMix->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId());
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->co2eq());
        $this->assertSame('IPCC', $copied->source());

        $this->assertNotNull($persisted->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId()));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::random();
        $electricityMix->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $electricityMix->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testThrowsWhenTargetYearIsFuture(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add(ElectricityMixMother::random());

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(
                sourceYear: 2024,
                emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(),
                targetYear: 9999,
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testIsHandled(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new CreateEmissionFactorsYearRequest(sourceYear: 2024)));
    }

    private function createSUT(ElectricityMixRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new ElectricityMixAuthorizer());
    }
}
