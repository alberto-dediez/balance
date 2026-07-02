<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List;

use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
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
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $merchandisingItem = MerchandisingItemMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id());
        $merchandisingItem->setEmissionFactor($ef2022, $ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id());
        $merchandisingItem->setEmissionFactor($ef2024, $ef2024);
        $repository->add($merchandisingItem);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->merchandisingItems);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();
        $repository->add(MerchandisingItemMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();
        $repository->add(MerchandisingItemMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(MerchandisingItemRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new MerchandisingItemAuthorizer());
    }
}
