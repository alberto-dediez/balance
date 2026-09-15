<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\EmissionFactor\List;

use App\CarbonFootprint\Application\RefrigerantGas\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\RefrigerantGas\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
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
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $refrigerantGas = RefrigerantGasMother::random();
        $refrigerantGas->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $refrigerantGas->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($refrigerantGas);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->refrigerantGases);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add(RefrigerantGasMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add(RefrigerantGasMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(RefrigerantGasRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new RefrigerantGasAuthorizer());
    }
}
