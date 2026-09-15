<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\EmissionFactor\List;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
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
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::random();
        $electricityMix->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $electricityMix->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->electricityMixes);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add(ElectricityMixMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add(ElectricityMixMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(ElectricityMixRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new ElectricityMixAuthorizer());
    }
}
