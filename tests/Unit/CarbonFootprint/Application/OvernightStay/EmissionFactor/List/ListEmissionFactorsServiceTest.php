<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\EmissionFactor\List;

use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
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
        $repository = OvernightStayRepositoryMother::inMemory();

        $overnightStay = OvernightStayMother::random();
        $overnightStay->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $overnightStay->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($overnightStay);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->overnightStays);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();
        $repository->add(OvernightStayMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();
        $repository->add(OvernightStayMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(OvernightStayRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new OvernightStayAuthorizer());
    }
}
