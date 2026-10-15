<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\EmissionFactor\List;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\CapitalGood\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
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
        $repository = CapitalGoodRepositoryMother::inMemory();

        $capitalGood = CapitalGoodMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $capitalGood->setEmissionFactor($ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $capitalGood->setEmissionFactor($ef2024);
        $repository->add($capitalGood);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->capitalGoods);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add(CapitalGoodMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add(CapitalGoodMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(CapitalGoodRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new CapitalGoodAuthorizer());
    }
}
