<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\EmissionFactor\List;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\EconomicSector\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
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
        $repository = EconomicSectorRepositoryMother::inMemory();

        $economicSector = EconomicSectorMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $economicSector->setEmissionFactor($ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $economicSector->setEmissionFactor($ef2024);
        $repository->add($economicSector);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->economicSectors);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add(EconomicSectorMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add(EconomicSectorMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(EconomicSectorRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new EconomicSectorAuthorizer());
    }
}
