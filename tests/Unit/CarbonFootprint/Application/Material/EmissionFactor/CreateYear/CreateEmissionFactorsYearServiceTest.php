<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\Material\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\Material\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\Material\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
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
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();
        $ef = new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), source: 'IPCC');
        $material->setEmissionFactor($ef, $ef);
        $repository->add($material);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($material->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId());
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->newFactor()->co2eq());
        $this->assertSame('IPCC', $copied->newFactor()->source());

        // The source year is preserved untouched.
        $this->assertNotNull($persisted->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId()));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $material->setEmissionFactor($ef2024, $ef2024);
        $ef2025 = new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $material->setEmissionFactor($ef2025, $ef2025);
        $repository->add($material);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    private function createSUT(MaterialRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new MaterialAuthorizer());
    }
}
