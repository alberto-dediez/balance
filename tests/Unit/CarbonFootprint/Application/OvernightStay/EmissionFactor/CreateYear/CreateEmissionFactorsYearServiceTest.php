<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
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
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();
        $overnightStay->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), source: 'IPCC'));
        $repository->add($overnightStay);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($overnightStay->id());
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
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();
        $overnightStay->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $overnightStay->setEmissionFactor(new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::defaultId()));
        $repository->add($overnightStay);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    private function createSUT(OvernightStayRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new OvernightStayAuthorizer());
    }
}
