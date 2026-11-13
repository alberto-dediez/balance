<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityService;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\PropertyActivityAnswerBuilder;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PropertyActivityAnswerBuilderTest extends TestCase
{
    private const YEAR = 2025;
    private const ACCOUNT_ID = 'account-id';

    private InMemoryActivityRepository $activityRepository;
    private Combustible $combustible;
    private Transport $transport;
    private RegisterCombustibleActivityService $registerCombustible;
    private RegisterTransportActivityService $registerTransport;
    private RegisterReportedEmissionsActivityService $registerReportedEmissions;
    private PropertyActivityAnswerBuilder $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->combustible = CombustibleMother::from('Gas natural', 'combustible-1', Unit::CubicMeter);
        $this->combustible->setEmissionFactor($this->factor('2.0', Unit::CubicMeter));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->transport = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $this->transport->setEmissionFactor($this->factor('0.5', Unit::TonneKilometre));
        $transportRepository = TransportRepositoryMother::inMemory();
        $transportRepository->add($this->transport);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();

        $this->registerCombustible = new RegisterCombustibleActivityService($this->activityRepository, $authorizer, new CombustibleEmissionFactorSelector($combustibleRepository), $calculator);
        $this->registerTransport = new RegisterTransportActivityService($this->activityRepository, $authorizer, new TransportEmissionFactorSelector($transportRepository), $calculator);
        $this->registerReportedEmissions = new RegisterReportedEmissionsActivityService($this->activityRepository, $authorizer, new ReportedEmissionsEmissionFactorSelector(), $calculator);

        $this->sut = new PropertyActivityAnswerBuilder($this->activityRepository);
    }

    public function testBuildsASectionAnswerPerSectionWithActivitiesWithoutExistingActivityId(): void
    {
        $footprint = $this->propertyFootprint();
        $this->addCombustible($footprint, 10.0, 'Factura 1');
        $this->addCombustible($footprint, 20.0, null);
        $this->addTransport($footprint);

        $built = $this->sut->build($footprint, Scope::Scope1);

        $this->assertSame(3, $built->count);
        $this->assertCount(2, $built->sectionAnswers);
        $this->assertSame('combustibles', $built->sectionAnswers[0]->definition->key);
        $this->assertSame('vehicles', $built->sectionAnswers[1]->definition->key);

        $combustibleAnswers = $built->sectionAnswers[0]->answers;
        $this->assertCount(2, $combustibleAnswers);
        $this->assertInstanceOf(CombustibleAnswer::class, $combustibleAnswers[0]);
        $this->assertTrue($combustibleAnswers[0]->combustibleId->equals($this->combustible->id()));
        $this->assertSame(10.0, $combustibleAnswers[0]->quantity->value());
        $this->assertSame('Factura 1', $combustibleAnswers[0]->source);
        $this->assertNull($combustibleAnswers[1]->source);

        $transportAnswer = $built->sectionAnswers[1]->answers[0];
        $this->assertInstanceOf(TransportAnswer::class, $transportAnswer);
        $this->assertNotNull($transportAnswer->weight);
        $this->assertSame(2500.0, $transportAnswer->weight->value());
        $this->assertSame(350.0, $transportAnswer->distanceKm);

        foreach ($built->sectionAnswers as $sectionAnswer) {
            foreach ($sectionAnswer->answers as $answer) {
                $this->assertNull($answer->existingActivityId);
            }
        }
    }

    public function testOmitsEmptySections(): void
    {
        $footprint = $this->propertyFootprint();
        $this->addCombustible($footprint, 10.0, null);

        $built = $this->sut->build($footprint, Scope::Scope1);

        $this->assertCount(1, $built->sectionAnswers);
        $this->assertSame(Source::FixedFacilities, $built->sectionAnswers[0]->definition->source);
        $this->assertSame(0, $this->sut->build($footprint, Scope::Scope2)->count);
        $this->assertSame([], $this->sut->build($footprint, Scope::Scope2)->sectionAnswers);
    }

    public function testSumsTheSourceEmissions(): void
    {
        $footprint = $this->propertyFootprint();
        $this->addCombustible($footprint, 10.0, null);
        $this->addCombustible($footprint, 20.0, null);
        $this->addTransport($footprint);

        $built = $this->sut->build($footprint, Scope::Scope1);

        $this->assertEqualsWithDelta(497.5, (float) $built->emissions->co2eq(), 0.0001);
    }

    public function testThrowsOnAReportedEmissionsActivity(): void
    {
        $footprint = $this->propertyFootprint();
        $response = ($this->registerReportedEmissions)(new RegisterReportedEmissionsActivityRequest(
            accountId: new AccountId(self::ACCOUNT_ID),
            description: 'Reparto 2025',
            quantity: new Quantity(1.2, Unit::Tonne),
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), $this->ownerUser());
        $footprint->addActivity($response->activityId, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $this->expectException(\LogicException::class);

        $this->sut->build($footprint, Scope::Scope1);
    }

    private function propertyFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::from('property-1', (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
    }

    private function addCombustible(PropertyCarbonFootprint $footprint, float $quantity, ?string $source): void
    {
        $response = ($this->registerCombustible)(new RegisterCombustibleActivityRequest(
            accountId: new AccountId(self::ACCOUNT_ID),
            combustibleId: $this->combustible->id(),
            quantity: new Quantity($quantity, Unit::CubicMeter),
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            source: $source,
        ), $this->ownerUser());

        $footprint->addActivity($response->activityId, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
    }

    private function addTransport(PropertyCarbonFootprint $footprint): void
    {
        $response = ($this->registerTransport)(new RegisterTransportActivityRequest(
            accountId: new AccountId(self::ACCOUNT_ID),
            transportId: $this->transport->id(),
            quantity: new Quantity(875.0, Unit::TonneKilometre),
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            weight: new Quantity(2500.0, Unit::Kilogram),
            distanceKm: 350.0,
        ), $this->ownerUser());

        $footprint->addActivity($response->activityId, new ActivityPlacement(Scope::Scope1, Source::OwnVehicles));
    }

    private function factor(string $co2eq, Unit $unit): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2eq,
            ch4: '0',
            n2o: '0',
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: $unit,
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
    }
}
