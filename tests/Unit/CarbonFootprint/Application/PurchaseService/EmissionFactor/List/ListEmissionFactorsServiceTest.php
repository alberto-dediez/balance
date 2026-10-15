<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\EmissionFactor\List;

use App\CarbonFootprint\Application\PurchaseService\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\PurchaseService\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
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
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $purchaseService = PurchaseServiceMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $purchaseService->setEmissionFactor($ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $purchaseService->setEmissionFactor($ef2024);
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->purchaseServices);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add(PurchaseServiceMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add(PurchaseServiceMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(PurchaseServiceRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new PurchaseServiceAuthorizer());
    }
}
