<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Trip\TripEmissions;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use PHPUnit\Framework\TestCase;

class TripEmissionsCalculatorTest extends TestCase
{
    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryOvernightStayRepository $overnightStayRepository;

    private EmissionFactorGroupId $emissionFactorGroupId;

    private int $calculationYear;

    private TripEmissionsCalculator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->emissionFactorGroupId = EmissionFactorGroupId::generate();
        $this->calculationYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $activityEmissionsResolver = new ActivityEmissionsResolver(
            new EmissionFactorSelectorRegistry([
                new MobilityEmissionFactorSelector($this->mobilityRepository),
                new OvernightStayEmissionFactorSelector($this->overnightStayRepository),
            ]),
            EmissionsCalculatorMother::create(),
        );

        $this->sut = new TripEmissionsCalculator($activityEmissionsResolver, $this->overnightStayRepository);
    }

    public function testCalculatesTheEmissionsOfTheKilometresOfASingleMobility(): void
    {
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $tripEmissions = $this->calculate([(string) $mobility->id() => 100.0], 0.0);

        $this->assertEqualsWithDelta(20.0, (float) $tripEmissions->mobility->co2eq(), 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $tripEmissions->overnight->co2eq(), 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $tripEmissions->total()->co2eq(), 0.001);
        $this->assertFalse($tripEmissions->overnightStayCatalogMissing);
    }

    public function testAddsUpTheEmissionsOfEveryMobility(): void
    {
        $car = $this->mobilityWithFactor('Coche', '0.2');
        $train = $this->mobilityWithFactor('Tren', '0.05');

        $tripEmissions = $this->calculate([
            (string) $car->id() => 100.0,
            (string) $train->id() => 200.0,
        ], 0.0);

        $this->assertEqualsWithDelta(30.0, (float) $tripEmissions->mobility->co2eq(), 0.001);
    }

    public function testIgnoresMobilitiesWithoutKilometres(): void
    {
        $car = $this->mobilityWithFactor('Coche', '0.2');
        $unknown = MobilityMother::fromName('Sin factor');
        $this->mobilityRepository->add($unknown);

        $tripEmissions = $this->calculate([
            (string) $car->id() => 100.0,
            (string) $unknown->id() => 0.0,
        ], 0.0);

        $this->assertEqualsWithDelta(20.0, (float) $tripEmissions->mobility->co2eq(), 0.001);
    }

    public function testAddsTheEmissionsOfTheNights(): void
    {
        $mobility = $this->mobilityWithFactor('Coche', '0.2');
        $this->overnightStayWithFactor('4.0');

        $tripEmissions = $this->calculate([(string) $mobility->id() => 100.0], 2.0);

        $this->assertEqualsWithDelta(20.0, (float) $tripEmissions->mobility->co2eq(), 0.001);
        $this->assertEqualsWithDelta(8.0, (float) $tripEmissions->overnight->co2eq(), 0.001);
        $this->assertEqualsWithDelta(28.0, (float) $tripEmissions->total()->co2eq(), 0.001);
        $this->assertFalse($tripEmissions->overnightStayCatalogMissing);
    }

    public function testFlagsTheMissingOvernightStayCatalog(): void
    {
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $tripEmissions = $this->calculate([(string) $mobility->id() => 100.0], 2.0);

        $this->assertTrue($tripEmissions->overnightStayCatalogMissing);
        $this->assertEqualsWithDelta(0.0, (float) $tripEmissions->overnight->co2eq(), 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $tripEmissions->total()->co2eq(), 0.001);
    }

    public function testDoesNotUseAnOvernightStayOfAnotherCategory(): void
    {
        $mobility = $this->mobilityWithFactor('Coche', '0.2');
        $this->overnightStayWithFactor('4.0', CategoryOvernight::OrganizingTeam);

        $tripEmissions = $this->calculate([(string) $mobility->id() => 100.0], 2.0);

        $this->assertTrue($tripEmissions->overnightStayCatalogMissing);
        $this->assertEqualsWithDelta(0.0, (float) $tripEmissions->overnight->co2eq(), 0.001);
    }

    public function testDoesNotCalculateOvernightEmissionsWithoutNights(): void
    {
        $mobility = $this->mobilityWithFactor('Coche', '0.2');
        $this->overnightStayWithFactor('4.0');

        $tripEmissions = $this->calculate([(string) $mobility->id() => 100.0], 0.0);

        $this->assertEqualsWithDelta(0.0, (float) $tripEmissions->overnight->co2eq(), 0.001);
        $this->assertFalse($tripEmissions->overnightStayCatalogMissing);
    }

    public function testCalculatesNoEmissionsWithoutKilometresAndNights(): void
    {
        $tripEmissions = $this->calculate([], 0.0);

        $this->assertEqualsWithDelta(0.0, (float) $tripEmissions->total()->co2eq(), 0.001);
    }

    /** @param array<string, float> $kmByMobilityId */
    private function calculate(array $kmByMobilityId, float $nights): TripEmissions
    {
        return $this->sut->calculate($kmByMobilityId, $nights, $this->emissionFactorGroupId, $this->calculationYear);
    }

    private function mobilityWithFactor(string $name, string $co2eq): Mobility
    {
        $mobility = MobilityMother::fromName($name);

        $mobility->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Kilometer,
        ));

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    private function overnightStayWithFactor(string $co2eq, CategoryOvernight $category = CategoryOvernight::Attendees): void
    {
        $overnightStay = OvernightStayMother::from(
            name: 'Hotel',
            id: (string) OvernightStayId::generate(),
            category: $category,
        );

        $overnightStay->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Pernoctacion,
        ));

        $this->overnightStayRepository->add($overnightStay);
    }
}
