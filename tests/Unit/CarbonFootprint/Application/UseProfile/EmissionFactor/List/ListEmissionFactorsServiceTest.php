<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\UseProfile\EmissionFactor\List;

use App\CarbonFootprint\Application\UseProfile\EmissionFactor\List\ListEmissionFactorsRequest;
use App\CarbonFootprint\Application\UseProfile\EmissionFactor\List\ListEmissionFactorsService;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
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
        $repository = UseProfileRepositoryMother::inMemory();

        $useProfile = UseProfileMother::random();
        $ef2022 = new EmissionFactor('1', '1', '1', '1', 2022, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $useProfile->setEmissionFactor($ef2022);
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $useProfile->setEmissionFactor($ef2024);
        $repository->add($useProfile);

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame(2024, $response->year);
        $this->assertCount(1, $response->useProfiles);
    }

    public function testResolvesCurrentYearWhenNoData(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add(UseProfileMother::random());

        $sut = $this->createSUT($repository);

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId()), UserMother::adminUser());

        $this->assertSame($currentYear, $response->year);
        $this->assertFalse($response->canGoNext);
    }

    public function testRespectsRequestedYearAndCanGoNext(): void
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add(UseProfileMother::random());

        $sut = $this->createSUT($repository);

        $response = $sut(new ListEmissionFactorsRequest(emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), year: 2010), UserMother::adminUser());

        $this->assertSame(2010, $response->year);
        $this->assertTrue($response->canGoNext);
    }

    private function createSUT(UseProfileRepository $repository): ListEmissionFactorsService
    {
        return new ListEmissionFactorsService($repository, new UseProfileAuthorizer());
    }
}
