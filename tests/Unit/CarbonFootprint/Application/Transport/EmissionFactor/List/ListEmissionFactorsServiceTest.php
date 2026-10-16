<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\EmissionFactor\List;

use App\CarbonFootprint\Application\Transport\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\Transport\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
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
        $repository = TransportRepositoryMother::inMemory();

        $transport = TransportMother::random();
        $transport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $transport->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($transport);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->transports);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = TransportRepositoryMother::inMemory();
        $repository->add(TransportMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = TransportRepositoryMother::inMemory();
        $repository->add(TransportMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::default()->id(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(TransportRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new TransportAuthorizer());
    }
}
