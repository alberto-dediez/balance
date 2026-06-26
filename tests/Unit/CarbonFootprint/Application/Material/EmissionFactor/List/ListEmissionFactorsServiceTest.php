<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\EmissionFactor\List;

use App\CarbonFootprint\Application\Material\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\Material\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
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
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022);
        $material->setEmissionFactor($ef2022, $ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024);
        $material->setEmissionFactor($ef2024, $ef2024);
        $repository->add($material);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->materials);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = MaterialRepositoryMother::inMemory();
        $repository->add(MaterialMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = MaterialRepositoryMother::inMemory();
        $repository->add(MaterialMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(MaterialRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new MaterialAuthorizer());
    }
}
