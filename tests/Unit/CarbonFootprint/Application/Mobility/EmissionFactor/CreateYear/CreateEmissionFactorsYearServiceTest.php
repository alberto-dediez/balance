<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\Mobility\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
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
        $repository = MobilityRepositoryMother::inMemory();

        $mobility = MobilityMother::random();
        $mobility->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), source: 'IPCC'));
        $repository->add($mobility);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($mobility->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId());
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->co2eq());
        $this->assertSame('IPCC', $copied->source());

        // The source year is preserved untouched.
        $this->assertNotNull($persisted->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId()));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $mobility = MobilityMother::random();
        $mobility->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $mobility->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $repository->add($mobility);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    private function createSUT(MobilityRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new MobilityAuthorizer());
    }
}
