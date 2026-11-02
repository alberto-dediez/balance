<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\SectorBenchmark\Refresh;

use App\CarbonFootprint\Application\SectorBenchmark\Refresh\RefreshSectorBenchmarksRequest;
use App\CarbonFootprint\Application\SectorBenchmark\Refresh\RefreshSectorBenchmarksService;
use App\CarbonFootprint\Application\SectorBenchmark\SectorBenchmarkAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemorySectorBenchmarkRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class RefreshSectorBenchmarksServiceTest extends TestCase
{
    private const int YEAR = 2025;
    private const array TEN_HOTELS_OF_FIVE_ACCOUNTS = ['account-a', 'account-a', 'account-b', 'account-b', 'account-c', 'account-c', 'account-d', 'account-d', 'account-e', 'account-e'];

    private InMemoryPropertyRepository $properties;
    private InMemoryPropertyCarbonFootprintRepository $footprints;
    private InMemorySectorBenchmarkRepository $benchmarks;
    private RefreshSectorBenchmarksService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->properties = PropertyRepositoryMother::inMemory();
        $this->footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->benchmarks = SectorBenchmarkRepositoryMother::inMemory();

        $this->sut = new RefreshSectorBenchmarksService(
            $this->footprints,
            CompanyCarbonFootprintRepositoryMother::inMemory(),
            $this->properties,
            $this->benchmarks,
            new IntensityIndicatorsCalculator(),
            new SectorBenchmarkAuthorizer(),
        );
    }

    public function testPublishesTheSegmentWithTenHotelsOfFiveAccounts(): void
    {
        $this->addHotels(self::TEN_HOTELS_OF_FIVE_ACCOUNTS, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $response = ($this->sut)(new RefreshSectorBenchmarksRequest(new \DateTimeImmutable('2026-01-15')), UserMother::adminUser());

        $this->assertSame(1, $response->benchmarks);
        $benchmark = $this->benchmarks->findBySegment(CarbonFootprintReportSubjectType::Property, PropertyType::Hotel->value, self::YEAR, IntensityIndicator::PerStay);
        $this->assertNotNull($benchmark);
        $this->assertSame('5.5000', $benchmark->median());
        $this->assertSame('3.2500', $benchmark->percentile25());
        $this->assertSame('7.7500', $benchmark->percentile75());
        $this->assertSame('5.5000', $benchmark->mean());
        $this->assertSame(10, $benchmark->sampleSize());
        $this->assertSame(5, $benchmark->accountCount());
    }

    public function testNineHotelsAreNotPublished(): void
    {
        $this->addHotels(['account-a', 'account-a', 'account-b', 'account-b', 'account-c', 'account-c', 'account-d', 'account-d', 'account-e'], [1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $response = ($this->sut)(new RefreshSectorBenchmarksRequest(new \DateTimeImmutable('2026-01-15')), UserMother::adminUser());

        $this->assertSame(0, $response->benchmarks);
        $this->assertSame([], $this->benchmarks->findAll());
    }

    public function testASegmentDominatedByOneAccountIsNotPublished(): void
    {
        $this->addHotels(['account-a', 'account-a', 'account-a', 'account-a', 'account-a', 'account-b', 'account-b', 'account-c', 'account-d', 'account-e'], [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $response = ($this->sut)(new RefreshSectorBenchmarksRequest(new \DateTimeImmutable('2026-01-15')), UserMother::adminUser());

        $this->assertSame(0, $response->benchmarks);
        $this->assertSame([], $this->benchmarks->findAll());
    }

    public function testReplacesThePreviousBenchmarks(): void
    {
        $this->benchmarks->add(SectorBenchmarkMother::computed('Camping', 2020));
        $this->addHotels(self::TEN_HOTELS_OF_FIVE_ACCOUNTS, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        ($this->sut)(new RefreshSectorBenchmarksRequest(new \DateTimeImmutable('2026-01-15')), UserMother::adminUser());

        $this->assertCount(1, $this->benchmarks->findAll());
        $this->assertNull($this->benchmarks->findBySegment(CarbonFootprintReportSubjectType::Property, 'Camping', 2020, IntensityIndicator::PerStay));
    }

    public function testOwnerIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RefreshSectorBenchmarksRequest(new \DateTimeImmutable('2026-01-15')), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-a')));
    }

    /**
     * @param list<string> $accounts
     * @param list<int>    $kgPerStay
     */
    private function addHotels(array $accounts, array $kgPerStay): void
    {
        foreach ($accounts as $index => $account) {
            $propertyId = 'hotel-' . $index;
            $this->properties->add(PropertyMother::from(new PropertyId($propertyId), 'Hotel ' . $index, new AccountId($account), new PropertyInfo(propertyType: PropertyType::Hotel)));

            $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered($propertyId, 'footprint-' . $index, $account, self::YEAR);
            $footprint->declareIntensityDenominators(new IntensityDenominators(stays: 1000));
            $total = (string) ($kgPerStay[$index] * 1000);
            $footprint->complete(new Emissions($total, $total));
            $this->footprints->add($footprint);
        }
    }
}
