<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update;

use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\OvernightStayEmissionFactorRow;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
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
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();
        $repository->add($overnightStay);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new OvernightStayEmissionFactorRow(
                    overnightStayId: (string) $overnightStay->id(),
                    overnightStayName: $overnightStay->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($overnightStay->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, EmissionFactorGroupId::default());
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();
        $overnightStay->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: EmissionFactorGroupId::default()));
        $repository->add($overnightStay);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new OvernightStayEmissionFactorRow(
                    overnightStayId: (string) $overnightStay->id(),
                    overnightStayName: $overnightStay->name(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($overnightStay->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, EmissionFactorGroupId::default()));
    }

    private function createSUT(OvernightStayRepository $repository): UpdateEmissionFactorsService
    {
        return new UpdateEmissionFactorsService($repository, new OvernightStayAuthorizer());
    }
}
