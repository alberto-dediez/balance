<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
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
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();
        $electricityMarketer->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022));
        $electricityMarketer->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024));
        $repository->add($electricityMarketer);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->electricityMarketers);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $repository->add(ElectricityMarketerMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $repository->add(ElectricityMarketerMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(ElectricityMarketerRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new ElectricityMarketerAuthorizer());
    }
}
