<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\EmissionFactor\List;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\Combustible\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
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
        $repository = CombustibleRepositoryMother::inMemory();

        $combustible = CombustibleMother::random();
        $combustible->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $combustible->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($combustible);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->combustibles);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();
        $repository->add(CombustibleMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();
        $repository->add(CombustibleMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(CombustibleRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new CombustibleAuthorizer());
    }
}
