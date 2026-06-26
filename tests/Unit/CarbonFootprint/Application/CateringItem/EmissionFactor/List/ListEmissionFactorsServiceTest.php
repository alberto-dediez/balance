<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\EmissionFactor\List;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
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
        $repository = CateringItemRepositoryMother::inMemory();

        $cateringItem = CateringItemMother::random();
        $cateringItem->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022));
        $cateringItem->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024));
        $repository->add($cateringItem);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->cateringItems);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();
        $repository->add(CateringItemMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();
        $repository->add(CateringItemMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(CateringItemRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new CateringItemAuthorizer());
    }
}
