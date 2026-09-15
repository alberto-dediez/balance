<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\CarbonFootprint;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Acceptance\Shared\AcceptancePlaceFinder;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final readonly class CarbonFootprintContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getMaterialRepository(): MaterialRepository
    {
        return $this->client->getContainer()->get(MaterialRepository::class);
    }

    public function getMerchandisingItemRepository(): MerchandisingItemRepository
    {
        return $this->client->getContainer()->get(MerchandisingItemRepository::class);
    }

    public function getCateringItemRepository(): CateringItemRepository
    {
        return $this->client->getContainer()->get(CateringItemRepository::class);
    }

    public function getCombustibleRepository(): CombustibleRepository
    {
        return $this->client->getContainer()->get(CombustibleRepository::class);
    }

    public function getRoadTransportRepository(): RoadTransportRepository
    {
        return $this->client->getContainer()->get(RoadTransportRepository::class);
    }

    public function getEmissionFactorGroupRepository(): EmissionFactorGroupRepository
    {
        return $this->client->getContainer()->get(EmissionFactorGroupRepository::class);
    }

    public function getElectricityMarketerRepository(): ElectricityMarketerRepository
    {
        return $this->client->getContainer()->get(ElectricityMarketerRepository::class);
    }

    public function getElectricityMixRepository(): ElectricityMixRepository
    {
        return $this->client->getContainer()->get(ElectricityMixRepository::class);
    }

    public function getRefrigerantGasRepository(): RefrigerantGasRepository
    {
        return $this->client->getContainer()->get(RefrigerantGasRepository::class);
    }

    public function getGdOFactorRepository(): GdOFactorRepository
    {
        return $this->client->getContainer()->get(GdOFactorRepository::class);
    }

    public function getMachineryFuelRepository(): MachineryFuelRepository
    {
        return $this->client->getContainer()->get(MachineryFuelRepository::class);
    }

    public function getMobilityRepository(): MobilityRepository
    {
        return $this->client->getContainer()->get(MobilityRepository::class);
    }

    public function getWasteRepository(): WasteRepository
    {
        return $this->client->getContainer()->get(WasteRepository::class);
    }

    public function getEventCarbonFootprintRepository(): EventCarbonFootprintRepository
    {
        return $this->client->getContainer()->get(EventCarbonFootprintRepository::class);
    }

    public function getProviderContributionRepository(): ProviderContributionRepository
    {
        return $this->client->getContainer()->get(ProviderContributionRepository::class);
    }

    public function getEmployeeContributionRepository(): EmployeeContributionRepository
    {
        return $this->client->getContainer()->get(EmployeeContributionRepository::class);
    }

    public function getAttendeeSurveyRepository(): AttendeeSurveyRepository
    {
        return $this->client->getContainer()->get(AttendeeSurveyRepository::class);
    }

    public function getAttendeeSurveyResponseRepository(): AttendeeSurveyResponseRepository
    {
        return $this->client->getContainer()->get(AttendeeSurveyResponseRepository::class);
    }

    private function ensureDefaultEmissionFactorGroupExists(): void
    {
        $repository = $this->getEmissionFactorGroupRepository();

        if (null !== $repository->find(EmissionFactorGroupMother::default()->id())) {
            return;
        }

        $repository->add(EmissionFactorGroupMother::default());
    }

    #[Given('event carbon footprint for event :eventId of account :account exists')]
    public function eventCarbonFootprintForEventOfAccountExists(string $eventId, string $account): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount($eventId, $account);

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('material :name with id :id exists')]
    public function materialWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $material = MaterialMother::fromNameAndId($name, $id);

        $this->getMaterialRepository()->add($material);
    }

    #[Given('material :name with id :id and emission factor for year :year exists')]
    public function materialWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $material = MaterialMother::fromNameAndId($name, $id);

        $ef = new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        );
        $material->setEmissionFactor($ef, $ef);

        $this->getMaterialRepository()->add($material);
    }

    #[Given('merchandising item :name with id :id exists')]
    public function merchandisingItemWithIdExists(string $name, string $id): void
    {
        $merchandisingItem = MerchandisingItemMother::fromNameAndId($name, $id);

        $this->getMerchandisingItemRepository()->add($merchandisingItem);
    }

    #[Given('catering item :name with id :id exists')]
    public function cateringItemWithIdExists(string $name, string $id): void
    {
        $cateringItem = CateringItemMother::fromNameAndId($name, $id);

        $this->getCateringItemRepository()->add($cateringItem);
    }

    #[Given('combustible :name with id :id exists')]
    public function combustibleWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $combustible = CombustibleMother::fromNameAndId($name, $id);

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Given('combustible :name with id :id and emission factor for year :year exists')]
    public function combustibleWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $combustible = CombustibleMother::fromNameAndId($name, $id);

        $combustible->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Given('emission factor group :name with id :id exists')]
    public function emissionFactorGroupWithIdExists(string $name, string $id): void
    {
        $emissionFactorGroup = EmissionFactorGroupMother::fromNameAndId($name, $id);

        $this->getEmissionFactorGroupRepository()->add($emissionFactorGroup);
    }

    #[Given('road transport :name with id :id exists')]
    public function roadTransportWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $roadTransport = RoadTransportMother::fromNameAndId($name, $id);

        $this->getRoadTransportRepository()->add($roadTransport);
    }

    #[Given('road transport :name with id :id and emission factor for year :year exists')]
    public function roadTransportWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $roadTransport = RoadTransportMother::fromNameAndId($name, $id);

        $roadTransport->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getRoadTransportRepository()->add($roadTransport);
    }

    #[Given('electricity marketer :name with id :id exists')]
    public function electricityMarketerWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMarketer = ElectricityMarketerMother::fromNameAndId($name, $id);

        $this->getElectricityMarketerRepository()->add($electricityMarketer);
    }

    #[Given('electricity marketer :name with id :id and emission factor for year :year exists')]
    public function electricityMarketerWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMarketer = ElectricityMarketerMother::fromNameAndId($name, $id);

        $electricityMarketer->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getElectricityMarketerRepository()->add($electricityMarketer);
    }

    #[Given('electricity mix for country :country with id :id exists')]
    public function electricityMixWithIdExists(string $country, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMix = ElectricityMixMother::fromCountryAndId($country, $id);

        $this->getElectricityMixRepository()->add($electricityMix);
    }

    #[Given('electricity mix for country :country with id :id and emission factor for year :year exists')]
    public function electricityMixWithEmissionFactorExists(string $country, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMix = ElectricityMixMother::fromCountryAndId($country, $id);

        $electricityMix->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getElectricityMixRepository()->add($electricityMix);
    }

    #[Given('refrigerant gas :name with id :id exists')]
    public function refrigerantGasWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $refrigerantGas = RefrigerantGasMother::fromNameAndId($name, $id);

        $this->getRefrigerantGasRepository()->add($refrigerantGas);
    }

    #[Given('refrigerant gas :name with id :id and emission factor for year :year exists')]
    public function refrigerantGasWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $refrigerantGas = RefrigerantGasMother::fromNameAndId($name, $id);

        $refrigerantGas->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getRefrigerantGasRepository()->add($refrigerantGas);
    }

    #[Given('gdo factor :name with id :id exists')]
    public function gdOFactorWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $gdOFactor = GdOFactorMother::fromNameAndId($name, $id);

        $this->getGdOFactorRepository()->add($gdOFactor);
    }

    #[Given('gdo factor :name with id :id and emission factor for year :year exists')]
    public function gdOFactorWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $gdOFactor = GdOFactorMother::fromNameAndId($name, $id);

        $gdOFactor->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getGdOFactorRepository()->add($gdOFactor);
    }

    #[Given('machinery fuel :name with id :id exists')]
    public function machineryFuelWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $machineryFuel = MachineryFuelMother::fromNameAndId($name, $id);

        $this->getMachineryFuelRepository()->add($machineryFuel);
    }

    #[Given('machinery fuel :name with id :id and emission factor for year :year exists')]
    public function machineryFuelWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $machineryFuel = MachineryFuelMother::fromNameAndId($name, $id);

        $machineryFuel->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getMachineryFuelRepository()->add($machineryFuel);
    }

    #[Given('mobility :name with id :id exists')]
    public function mobilityWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $mobility = MobilityMother::fromNameAndId($name, $id);

        $this->getMobilityRepository()->add($mobility);
    }

    #[Given('mobility :name with id :id and travel mode :travelMode exists')]
    public function mobilityWithIdAndTravelModeExists(string $name, string $id, string $travelMode): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $mobility = MobilityMother::fromNameIdAndTravelMode($name, $id, TravelMode::from($travelMode));

        $this->getMobilityRepository()->add($mobility);
    }

    #[Then('mobility :id has travel mode :travelMode')]
    public function mobilityHasTravelMode(string $id, string $travelMode): void
    {
        $mobility = $this->getMobilityRepository()->find(new MobilityId($id));

        Assert::assertNotNull($mobility);
        Assert::assertSame(TravelMode::from($travelMode), $mobility->travelMode());
    }

    #[Then('mobility :id has no travel mode')]
    public function mobilityHasNoTravelMode(string $id): void
    {
        $mobility = $this->getMobilityRepository()->find(new MobilityId($id));

        Assert::assertNotNull($mobility);
        Assert::assertNull($mobility->travelMode());
    }

    #[Then('mobility named :name has travel mode :travelMode')]
    public function mobilityNamedHasTravelMode(string $name, string $travelMode): void
    {
        Assert::assertSame(TravelMode::from($travelMode), $this->findMobilityByName($name)->travelMode());
    }

    #[Then('mobility named :name has no travel mode')]
    public function mobilityNamedHasNoTravelMode(string $name): void
    {
        Assert::assertNull($this->findMobilityByName($name)->travelMode());
    }

    private function findMobilityByName(string $name): Mobility
    {
        $mobilities = $this->getMobilityRepository()->findByFilter(new MobilityFilter(name: $name));

        Assert::assertCount(1, $mobilities);

        $mobility = reset($mobilities);

        Assert::assertInstanceOf(Mobility::class, $mobility);

        return $mobility;
    }

    #[Given('mobility :name with id :id and emission factor for year :year exists')]
    public function mobilityWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $mobility = MobilityMother::fromNameAndId($name, $id);

        $mobility->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: Unit::Kilometer,
            source: 'IPCC',
        ));

        $this->getMobilityRepository()->add($mobility);
    }

    #[Given('mobility :name with id :id and emission factor for the current year exists')]
    public function mobilityWithEmissionFactorForTheCurrentYearExists(string $name, string $id): void
    {
        $this->mobilityWithEmissionFactorExists($name, $id, (new \DateTimeImmutable('NOW'))->format('Y'));
    }

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $waste = WasteMother::fromNameAndId($name, $id);

        $this->getWasteRepository()->add($waste);
    }

    #[Given('waste :name with id :id and emission factor for year :year exists')]
    public function wasteWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $waste = WasteMother::fromNameAndId($name, $id);

        $waste->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getWasteRepository()->add($waste);
    }

    public function getOvernightStayRepository(): OvernightStayRepository
    {
        return $this->client->getContainer()->get(OvernightStayRepository::class);
    }

    #[Given('overnight stay :name with id :id exists')]
    public function overnightStayWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $overnightStay = OvernightStayMother::fromNameAndId($name, $id);

        $this->getOvernightStayRepository()->add($overnightStay);
    }

    #[Given('overnight stay :name with id :id and emission factor for year :year exists')]
    public function overnightStayWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $overnightStay = OvernightStayMother::fromNameAndId($name, $id);

        $overnightStay->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: Unit::Pernoctacion,
            source: 'IPCC',
        ));

        $this->getOvernightStayRepository()->add($overnightStay);
    }

    #[Given('overnight stay :name with id :id and emission factor for the current year exists')]
    public function overnightStayWithEmissionFactorForTheCurrentYearExists(string $name, string $id): void
    {
        $this->overnightStayWithEmissionFactorExists($name, $id, (new \DateTimeImmutable('NOW'))->format('Y'));
    }

    #[Given('event carbon footprint with id :id for event :eventId of account :account with materials answered exists')]
    public function eventCarbonFootprintWithIdForEventOfAccountWithMaterialsAnsweredExists(string $id, string $eventId, string $account): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: $eventId, id: $id, accountId: $account);
        $eventCarbonFootprint->answerPhase(Phase::Materials);

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint with id :id for event :eventId of account :account with breakdown answered exists')]
    public function eventCarbonFootprintWithIdForEventOfAccountWithBreakdownAnsweredExists(string $id, string $eventId, string $account): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: $eventId, id: $id, accountId: $account);
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint with id :id for event :eventId of account :account with providers answered exists')]
    public function eventCarbonFootprintWithIdForEventOfAccountWithProvidersAnsweredExists(string $id, string $eventId, string $account): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: $eventId, id: $id, accountId: $account);
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);
        $eventCarbonFootprint->answerProviders();

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint with id :id for event :eventId of account :account with employees answered exists')]
    public function eventCarbonFootprintWithIdForEventOfAccountWithEmployeesAnsweredExists(string $id, string $eventId, string $account): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: $eventId, id: $id, accountId: $account);
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);
        $eventCarbonFootprint->answerProviders();
        $eventCarbonFootprint->answerEmployees();

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint with id :id for event :eventId of account :account completed exists')]
    public function eventCarbonFootprintWithIdForEventOfAccountCompletedExists(string $id, string $eventId, string $account): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: $eventId, id: $id, accountId: $account);
        $eventCarbonFootprint->answerPhase(Phase::Materials);
        $eventCarbonFootprint->answerPhase(Phase::Assembly);
        $eventCarbonFootprint->answerPhase(Phase::Celebration);
        $eventCarbonFootprint->answerPhase(Phase::Breakdown);
        $eventCarbonFootprint->complete(totalEmissions: new Emissions(), emissionsPerAttendee: new Emissions());

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('provider contribution with id :id for event carbon footprint :eventCarbonFootprintId and provider :providerId of account :account with token :token exists')]
    public function providerContributionWithIdExists(string $id, string $eventCarbonFootprintId, string $providerId, string $account, string $token): void
    {
        $providerContribution = new ProviderContribution(new ProviderContributionId($id));
        $providerContribution->register(
            eventCarbonFootprintId: new EventCarbonFootprintId($eventCarbonFootprintId),
            accountId: new AccountId($account),
            providerId: new ProviderId($providerId),
            token: $token,
            companyName: 'Provider Company',
        );

        $this->getProviderContributionRepository()->add($providerContribution);
    }

    #[Given('employee contribution with id :id for event carbon footprint :eventCarbonFootprintId and employee :employeeId of account :account with token :token exists')]
    public function employeeContributionWithIdExists(string $id, string $eventCarbonFootprintId, string $employeeId, string $account, string $token): void
    {
        $employeeContribution = new EmployeeContribution(new EmployeeContributionId($id));
        $employeeContribution->register(
            eventCarbonFootprintId: new EventCarbonFootprintId($eventCarbonFootprintId),
            accountId: new AccountId($account),
            employeeId: new EmployeeId($employeeId),
            token: $token,
            employeeName: 'Employee Name',
        );

        $this->getEmployeeContributionRepository()->add($employeeContribution);
    }

    #[Given('provider :providerId has a linked user')]
    public function providerHasALinkedUser(string $providerId): void
    {
        $providerRepository = $this->client->getContainer()->get(ProviderRepository::class);
        $provider = $providerRepository->find(new ProviderId($providerId));

        Assert::assertNotNull($provider);

        $user = UserMother::fromUsernameAndRole('provider-' . $providerId . '@contribution.internal', Role::Provider);
        $this->client->getContainer()->get(UserRepository::class)->add($user);

        $provider->linkUser($user->id());
        $providerRepository->add($provider);
    }

    #[Given('provider contribution :id is ignored')]
    public function providerContributionIsIgnored(string $id): void
    {
        $providerContribution = $this->getProviderContributionRepository()->find(new ProviderContributionId($id));

        Assert::assertNotNull($providerContribution);

        $providerContribution->ignore();

        $this->getProviderContributionRepository()->add($providerContribution);
    }

    #[Then('provider contribution :id should have status :status')]
    public function providerContributionShouldHaveStatus(string $id, string $status): void
    {
        $providerContribution = $this->getProviderContributionRepository()->find(new ProviderContributionId($id));

        Assert::assertNotNull($providerContribution);
        Assert::assertSame($status, $providerContribution->status()->value);
    }

    #[Then('provider contribution :id should not have consent')]
    public function providerContributionShouldNotHaveConsent(string $id): void
    {
        $providerContribution = $this->getProviderContributionRepository()->find(new ProviderContributionId($id));

        Assert::assertNotNull($providerContribution);
        Assert::assertFalse($providerContribution->hasConsent());
    }

    #[Then('provider contribution :id should have company name :companyName')]
    public function providerContributionShouldHaveCompanyName(string $id, string $companyName): void
    {
        $providerContribution = $this->getProviderContributionRepository()->find(new ProviderContributionId($id));

        Assert::assertNotNull($providerContribution);
        Assert::assertSame($companyName, $providerContribution->companyName());
    }

    #[Then('employee contribution :id should not have consent')]
    public function employeeContributionShouldNotHaveConsent(string $id): void
    {
        $employeeContribution = $this->getEmployeeContributionRepository()->find(new EmployeeContributionId($id));

        Assert::assertNotNull($employeeContribution);
        Assert::assertFalse($employeeContribution->hasConsent());
    }

    #[Then('employee contribution :id should have consent')]
    public function employeeContributionShouldHaveConsent(string $id): void
    {
        $employeeContribution = $this->getEmployeeContributionRepository()->find(new EmployeeContributionId($id));

        Assert::assertNotNull($employeeContribution);
        Assert::assertTrue($employeeContribution->hasConsent());
    }

    #[Then('employee contribution :id should have status :status')]
    public function employeeContributionShouldHaveStatus(string $id, string $status): void
    {
        $employeeContribution = $this->getEmployeeContributionRepository()->find(new EmployeeContributionId($id));

        Assert::assertNotNull($employeeContribution);
        Assert::assertSame($status, $employeeContribution->status()->value);
    }

    #[Then('employee contribution :id should have :phase data with mobility :mobilityId, distance :distanceKm, occupants :occupants, days :days and hotel nights :hotelNights')]
    public function employeeContributionShouldHavePhaseData(string $id, string $phase, string $mobilityId, float $distanceKm, int $occupants, int $days, int $hotelNights): void
    {
        $employeeContribution = $this->getEmployeeContributionRepository()->find(new EmployeeContributionId($id));

        Assert::assertNotNull($employeeContribution);

        [$worked, $mobility, $distance, $vehicleOccupants, $phaseDays, $hotelNightsAnswered] = match ($phase) {
            'assembly' => [
                $employeeContribution->workedAssembly(),
                $employeeContribution->assemblyMobilityId(),
                $employeeContribution->assemblyDistanceKm(),
                $employeeContribution->assemblyVehicleOccupants(),
                $employeeContribution->assemblyDays(),
                $employeeContribution->assemblyHotelNights(),
            ],
            'celebration' => [
                $employeeContribution->workedCelebration(),
                $employeeContribution->celebrationMobilityId(),
                $employeeContribution->celebrationDistanceKm(),
                $employeeContribution->celebrationVehicleOccupants(),
                $employeeContribution->celebrationDays(),
                $employeeContribution->celebrationHotelNights(),
            ],
            'breakdown' => [
                $employeeContribution->workedBreakdown(),
                $employeeContribution->breakdownMobilityId(),
                $employeeContribution->breakdownDistanceKm(),
                $employeeContribution->breakdownVehicleOccupants(),
                $employeeContribution->breakdownDays(),
                $employeeContribution->breakdownHotelNights(),
            ],
            default => throw new \InvalidArgumentException(sprintf('Unknown phase "%s".', $phase)),
        };

        Assert::assertTrue($worked);
        Assert::assertNotNull($mobility);
        Assert::assertSame($mobilityId, $mobility->id());
        Assert::assertSame($distanceKm, $distance);
        Assert::assertSame($occupants, $vehicleOccupants);
        Assert::assertSame($days, $phaseDays);
        Assert::assertSame($hotelNights, $hotelNightsAnswered);
    }

    #[Then('employee contribution :id should have step status :status')]
    public function employeeContributionShouldHaveStepStatus(string $id, string $status): void
    {
        $employeeContribution = $this->getEmployeeContributionRepository()->find(new EmployeeContributionId($id));

        Assert::assertNotNull($employeeContribution);
        Assert::assertSame($status, $employeeContribution->stepStatus()->value);
    }

    #[Then('provider :providerId should have a linked user')]
    public function providerShouldHaveALinkedUser(string $providerId): void
    {
        $provider = $this->client->getContainer()->get(ProviderRepository::class)->find(new ProviderId($providerId));

        Assert::assertNotNull($provider);
        Assert::assertNotNull($provider->userId());
    }

    #[Given('event :id has :estimatedAttendees estimated attendees')]
    public function eventHasEstimatedAttendees(string $id, int $estimatedAttendees): void
    {
        $eventRepository = $this->client->getContainer()->get(EventRepository::class);
        $event = $eventRepository->find(new EventId($id));

        Assert::assertNotNull($event);

        $event->update(
            name: $event->name(),
            location: $event->location(),
            propertyId: $event->propertyId(),
            companyId: $event->companyId(),
            size: $event->size(),
            estimatedAttendees: $estimatedAttendees,
            sector: $event->sector(),
            typology: $event->typology(),
            assembly: $event->assembly(),
            celebration: $event->celebration(),
            breakdown: $event->breakdown(),
        );

        $eventRepository->add($event);
    }

    #[Given('attendee survey with id :id and token :token for event :eventId of account :account exists')]
    public function attendeeSurveyWithIdAndTokenForEventOfAccountExists(string $id, string $token, string $eventId, string $account): void
    {
        $attendeeSurvey = new AttendeeSurvey(new AttendeeSurveyId($id));
        $attendeeSurvey->register(
            eventId: new EventId($eventId),
            accountId: new AccountId($account),
            token: $token,
        );

        $this->getAttendeeSurveyRepository()->add($attendeeSurvey);
    }

    #[Given('attendee survey :id is closed with :realAttendees real attendees and confidence level :level')]
    public function attendeeSurveyIsClosedWithRealAttendeesAndConfidenceLevel(string $id, int $realAttendees, int $level): void
    {
        $attendeeSurvey = $this->getAttendeeSurveyRepository()->find(new AttendeeSurveyId($id));

        Assert::assertNotNull($attendeeSurvey);

        $confidenceLevel = ConfidenceLevel::from($level);
        $responsesCount = $this->getAttendeeSurveyResponseRepository()->countByAttendeeSurveyId($attendeeSurvey->id());
        $marginOfError = MarginOfError::calculate($responsesCount, $realAttendees, $confidenceLevel);

        $attendeeSurvey->close($realAttendees, $confidenceLevel, $marginOfError);

        $this->getAttendeeSurveyRepository()->add($attendeeSurvey);
    }

    #[Given('attendee survey :id has venue location :label at :latitude, :longitude')]
    public function attendeeSurveyHasVenueLocationAt(string $id, string $label, string $latitude, string $longitude): void
    {
        $attendeeSurvey = $this->getAttendeeSurveyRepository()->find(new AttendeeSurveyId($id));

        Assert::assertNotNull($attendeeSurvey);

        $eventId = $attendeeSurvey->eventId();

        Assert::assertNotNull($eventId);

        $eventRepository = $this->client->getContainer()->get(EventRepository::class);
        $event = $eventRepository->find($eventId);

        Assert::assertNotNull($event);

        $event->setVenueLocation('venue-' . $id, $label, (float) $latitude, (float) $longitude);

        $eventRepository->add($event);
    }

    #[Given('place :reference named :label at :latitude, :longitude exists')]
    public function placeNamedAtExists(string $reference, string $label, string $latitude, string $longitude): void
    {
        $this->getPlaceFinder()->add($reference, $label, (float) $latitude, (float) $longitude);
    }

    #[Given('the places provider is disabled')]
    public function thePlacesProviderIsDisabled(): void
    {
        $this->getPlaceFinder()->disable();
    }

    private function getPlaceFinder(): AcceptancePlaceFinder
    {
        return $this->client->getContainer()->get(AcceptancePlaceFinder::class);
    }

    #[Given('attendee survey response with id :id for attendee survey :attendeeSurveyId using mobility :mobilityId exists')]
    public function attendeeSurveyResponseWithIdForAttendeeSurveyUsingMobilityExists(string $id, string $attendeeSurveyId, string $mobilityId): void
    {
        $response = new AttendeeSurveyResponse(new AttendeeSurveyResponseId($id));
        $response->register(
            attendeeSurveyId: new AttendeeSurveyId($attendeeSurveyId),
            cameFromAnotherCity: true,
            origin: null,
            usedHotel: true,
            accommodation: null,
            hotelNights: 2,
            hotelPeople: 1,
            intercityRoundTrip: false,
            intercityTrips: 2,
            movedWithinCity: true,
            cityOrigin: null,
            cityRoundTrip: false,
            cityTrips: 2,
            legs: [
                new ResolvedLeg(
                    position: 1,
                    kind: LegKind::Intercity,
                    mobilityId: new MobilityId($mobilityId),
                    vehicleOccupants: 1,
                    origin: new TripWaypoint(),
                    destination: new TripWaypoint(),
                    distanceKm: 100.0,
                    distanceSource: DistanceSource::Manual,
                ),
                new ResolvedLeg(
                    position: 1,
                    kind: LegKind::City,
                    mobilityId: new MobilityId($mobilityId),
                    vehicleOccupants: 1,
                    origin: new TripWaypoint(),
                    destination: new TripWaypoint(),
                    distanceKm: 10.0,
                    distanceSource: DistanceSource::Manual,
                ),
            ],
        );

        $this->getAttendeeSurveyResponseRepository()->add($response);
    }

    #[Given('event carbon footprint :id has a manual attendees mobility activity using mobility :mobilityId')]
    public function eventCarbonFootprintHasAManualAttendeesMobilityActivityUsingMobility(string $id, string $mobilityId): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $accountId = $eventCarbonFootprint->accountId();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        Assert::assertNotNull($accountId);
        Assert::assertNotNull($emissionFactorGroupId);

        $registerMobilityActivity = $this->client->getContainer()->get(RegisterMobilityActivityService::class);

        $response = $registerMobilityActivity(
            new RegisterMobilityActivityRequest(
                accountId: $accountId,
                mobilityId: new MobilityId($mobilityId),
                quantity: new Quantity(50.0, Unit::Kilometer),
                year: $eventCarbonFootprint->calculationYear(),
                emissionFactorGroupId: $emissionFactorGroupId,
            ),
            UserMother::adminUser(),
        );

        $eventCarbonFootprint->addActivity($response->activityId, Phase::Celebration, Source::AttendeesMobility);

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[When('I submit the attendees close form with :realAttendees real attendees and confidence level :level')]
    public function iSubmitTheAttendeesCloseFormWith(string $realAttendees, string $level): void
    {
        $crawler = $this->client->getCrawler();

        $form = $crawler->filter('form[name="attendees_close"]')->form();
        $form['realAttendees'] = $realAttendees;
        $form['confidenceLevel'] = $level;

        $this->client->submit($form);
    }

    #[Given('event carbon footprint :id has a waste activity using waste :wasteId in assembly')]
    public function eventCarbonFootprintHasAWasteActivityUsingWasteInAssembly(string $id, string $wasteId): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $accountId = $eventCarbonFootprint->accountId();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        Assert::assertNotNull($accountId);
        Assert::assertNotNull($emissionFactorGroupId);

        $registerWasteActivity = $this->client->getContainer()->get(RegisterWasteActivityService::class);

        $response = $registerWasteActivity(
            new RegisterWasteActivityRequest(
                accountId: $accountId,
                wasteId: new WasteId($wasteId),
                quantity: new Quantity(10.0, Unit::Kilogram),
                year: $eventCarbonFootprint->calculationYear(),
                emissionFactorGroupId: $emissionFactorGroupId,
            ),
            UserMother::adminUser(),
        );

        $eventCarbonFootprint->addActivity($response->activityId, Phase::Assembly, Source::WasteManagement);

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint :id has source :source excluded in phase :phase with reason :reason')]
    public function eventCarbonFootprintHasSourceExcludedInPhaseWithReason(string $id, string $source, string $phase, string $reason): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $eventCarbonFootprint->excludeSource(Phase::from($phase), Source::from($source), ExclusionReason::from($reason));

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint :id is completed')]
    public function eventCarbonFootprintIsCompleted(string $id): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $eventCarbonFootprint->complete(totalEmissions: new Emissions(), emissionsPerAttendee: new Emissions());

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[When('I submit the :phase step form excluding source :source with reason :reason and comment :comment')]
    public function iSubmitTheStepFormExcludingSource(string $phase, string $source, string $reason, string $comment): void
    {
        $phaseEnum = Phase::from($phase);
        $sourceEnum = Source::from($source);

        $crawler = $this->client->getCrawler();
        $form = $crawler->filter('form')->form();

        foreach ($phaseEnum->sections() as $section) {
            if ($section->source !== $sourceEnum) {
                continue;
            }

            foreach (array_keys($form->all()) as $name) {
                if (str_starts_with($name, $section->key . '[')) {
                    unset($form[$name]);
                }
            }
        }

        $excludedField = $form['exclusions[' . $source . '][excluded]'];
        Assert::assertInstanceOf(ChoiceFormField::class, $excludedField);
        $excludedField->tick();

        $form['exclusions[' . $source . '][reason]'] = $reason;
        $form['exclusions[' . $source . '][comment]'] = $comment;

        $this->client->submit($form);
    }

    #[Then('event carbon footprint :id should have source :source excluded in phase :phase with reason :reason')]
    public function eventCarbonFootprintShouldHaveSourceExcludedInPhaseWithReason(string $id, string $source, string $phase, string $reason): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $exclusion = $eventCarbonFootprint->exclusions()->forPhaseAndSource(Phase::from($phase), Source::from($source));

        Assert::assertNotNull($exclusion);
        Assert::assertSame($reason, $exclusion->reason()->value);
    }

    #[Then('event carbon footprint :id should have :count activities of source :source in phase :phase')]
    public function eventCarbonFootprintShouldHaveActivitiesOfSourceInPhase(string $id, int $count, string $source, string $phase): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $activities = $eventCarbonFootprint->activities()
            ->filterByPhase(Phase::from($phase))
            ->filterBySource(Source::from($source))
            ->toArray();

        Assert::assertCount($count, $activities);
    }

    #[Then('attendee survey :id should have :count responses')]
    public function attendeeSurveyShouldHaveResponses(string $id, int $count): void
    {
        Assert::assertSame($count, $this->getAttendeeSurveyResponseRepository()->countByAttendeeSurveyId(new AttendeeSurveyId($id)));
    }

    #[When('I go to the thank-you page of the only contribution with attendee survey token :token')]
    public function iGoToTheThankYouPageOfTheOnlyContribution(string $token): void
    {
        $this->client->request(
            'GET',
            sprintf('/es/carbon-footprint/attendee-survey/%s/contribution/%s/return', $token, $this->onlyContributionId()),
        );
    }

    #[When('I download the certificate of the only contribution with attendee survey token :token')]
    public function iDownloadTheCertificateOfTheOnlyContribution(string $token): void
    {
        $this->client->request(
            'GET',
            sprintf('/es/carbon-footprint/attendee-survey/%s/contribution/%s/certificate', $token, $this->onlyContributionId()),
        );
    }

    #[When('I email the certificate of the only contribution with attendee survey token :token')]
    public function iEmailTheCertificateOfTheOnlyContribution(string $token): void
    {
        $this->client->request(
            'POST',
            sprintf('/es/carbon-footprint/attendee-survey/%s/contribution/%s/certificate/email', $token, $this->onlyContributionId()),
        );
    }

    private function onlyContributionId(): string
    {
        $projectContributionRepository = $this->client->getContainer()->get(ProjectContributionRepository::class);
        $contributions = $projectContributionRepository->findByFilter(new ProjectContributionFilter(limit: 0));

        Assert::assertCount(1, $contributions);

        return (string) $contributions[0]->id();
    }

    #[Then('attendee survey for event :eventId should be closed')]
    public function attendeeSurveyForEventShouldBeClosed(string $eventId): void
    {
        $attendeeSurvey = $this->getAttendeeSurveyRepository()->findByEventId(new EventId($eventId));

        Assert::assertNotNull($attendeeSurvey);
        Assert::assertTrue($attendeeSurvey->status()->isClosed());
    }

    #[Then('event carbon footprint :id should have :count attendees mobility activities in celebration')]
    public function eventCarbonFootprintShouldHaveAttendeesMobilityActivitiesInCelebration(string $id, int $count): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $activities = $eventCarbonFootprint->activities()
            ->filterByPhase(Phase::Celebration)
            ->filterBySource(Source::AttendeesMobility)
            ->toArray();

        Assert::assertCount($count, $activities);
    }

    #[Then('event carbon footprint :id should have :count attendees overnight stay activities in celebration')]
    public function eventCarbonFootprintShouldHaveAttendeesOvernightStayActivitiesInCelebration(string $id, int $count): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $activities = $eventCarbonFootprint->activities()
            ->filterByPhase(Phase::Celebration)
            ->filterBySource(Source::AttendeesAccommodation)
            ->toArray();

        Assert::assertCount($count, $activities);
    }

    #[Given('trip calculation link with id :id and token :token for property :propertyId of account :account exists')]
    public function tripCalculationLinkWithIdAndTokenForPropertyOfAccountExists(string $id, string $token, string $propertyId, string $account): void
    {
        $tripCalculationLink = new TripCalculationLink(new TripCalculationLinkId($id));
        $tripCalculationLink->register(
            propertyId: new PropertyId($propertyId),
            accountId: new AccountId($account),
            token: $token,
        );

        $this->getTripCalculationLinkRepository()->add($tripCalculationLink);
    }

    #[Given('trip calculation link with id :id and token :token for property :propertyId of account :account with scope :scope exists')]
    public function tripCalculationLinkWithIdAndTokenForPropertyOfAccountWithScopeExists(string $id, string $token, string $propertyId, string $account, string $scope): void
    {
        $this->tripCalculationLinkWithIdAndTokenForPropertyOfAccountExists($id, $token, $propertyId, $account);
        $this->tripCalculationLinkHasScope($id, $scope);
    }

    #[Given('trip calculation link :id has scope :scope')]
    public function tripCalculationLinkHasScope(string $id, string $scope): void
    {
        $tripCalculationLink = $this->getTripCalculationLinkRepository()->find(new TripCalculationLinkId($id));

        Assert::assertNotNull($tripCalculationLink);

        $tripCalculationLink->updateScope(TripCalculationScope::from($scope));

        $this->getTripCalculationLinkRepository()->add($tripCalculationLink);
    }

    #[Given('property :id is located at :latitude, :longitude')]
    public function propertyIsLocatedAt(string $id, string $latitude, string $longitude): void
    {
        $propertyRepository = $this->client->getContainer()->get(PropertyRepository::class);
        $property = $propertyRepository->find(new PropertyId($id));

        Assert::assertNotNull($property);

        $property->update(
            name: $property->name(),
            accountId: $property->accountId(),
            info: new PropertyInfo(latitude: $latitude, longitude: $longitude),
        );

        $propertyRepository->add($property);
    }

    #[Then('trip calculation link :id should have :count trip calculations')]
    public function tripCalculationLinkShouldHaveTripCalculations(string $id, int $count): void
    {
        Assert::assertCount($count, $this->getTripCalculationRepository()->findByLinkId(new TripCalculationLinkId($id)));
    }

    #[Given('active public landing for :ownerType :ownerId of account :account with title :title exists')]
    public function activePublicLandingForOwnerOfAccountWithTitleExists(string $ownerType, string $ownerId, string $account, string $title): void
    {
        $publicLanding = new PublicLanding(PublicLandingId::generate());
        $publicLanding->register(
            ownerType: PublicLandingOwnerType::from($ownerType),
            ownerId: $ownerId,
            accountId: new AccountId($account),
        );

        $publicLanding->update(
            active: true,
            title: $title,
            subtitle: 'Antes de empezar',
            intro: 'Este proyecto mide su impacto.',
            heroImage: null,
            ctaLabel: 'Quiero calcular mi huella',
        );

        $publicLanding->setBlock(0, null, 'Por qué lo hacemos', 'Porque medir es el primer paso.');

        $this->getPublicLandingRepository()->add($publicLanding);
    }

    #[Then('public landing for :ownerType :ownerId should be active with title :title')]
    public function publicLandingForOwnerShouldBeActiveWithTitle(string $ownerType, string $ownerId, string $title): void
    {
        $publicLanding = $this->getPublicLandingRepository()->findByOwner(PublicLandingOwnerType::from($ownerType), $ownerId);

        Assert::assertNotNull($publicLanding);
        Assert::assertTrue($publicLanding->isActive());
        Assert::assertSame($title, $publicLanding->title());
    }

    #[Then('public landing for :ownerType :ownerId should have :count blocks')]
    public function publicLandingForOwnerShouldHaveBlocks(string $ownerType, string $ownerId, int $count): void
    {
        $publicLanding = $this->getPublicLandingRepository()->findByOwner(PublicLandingOwnerType::from($ownerType), $ownerId);

        Assert::assertNotNull($publicLanding);
        Assert::assertCount($count, $publicLanding->blocks());
    }

    #[Given('event carbon footprint :id has a report generated by :username')]
    public function eventCarbonFootprintHasAReportGeneratedBy(string $id, string $username): void
    {
        $user = $this->client->getContainer()->get(UserRepository::class)->findByUsername($username);

        Assert::assertNotNull($user);

        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $accountId = $eventCarbonFootprint->accountId();

        Assert::assertNotNull($accountId);

        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register($eventCarbonFootprint->id(), $accountId, $user->id());
        $report->translate(sprintf('%s/%s-es.pdf', $accountId, $report->id()), Locale::Spanish);
        $report->generate();

        $this->getEventCarbonFootprintReportRepository()->add($report);
    }

    #[Given('event carbon footprint :id has a failed report by :username')]
    public function eventCarbonFootprintHasAFailedReportBy(string $id, string $username): void
    {
        $user = $this->client->getContainer()->get(UserRepository::class)->findByUsername($username);

        Assert::assertNotNull($user);

        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $accountId = $eventCarbonFootprint->accountId();

        Assert::assertNotNull($accountId);

        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register($eventCarbonFootprint->id(), $accountId, $user->id());
        $report->fail('Gemini failure');

        $this->getEventCarbonFootprintReportRepository()->add($report);
    }

    #[Then('the report of event carbon footprint :id should be :status')]
    public function theReportOfEventCarbonFootprintShouldBe(string $id, string $status): void
    {
        $report = $this->getEventCarbonFootprintReportRepository()->findByEventCarbonFootprintId(new EventCarbonFootprintId($id));

        Assert::assertNotNull($report);
        Assert::assertSame($status, $report->status()->value);
    }

    #[Given('account :accountId compensated :tonnes tonnes of event :eventId')]
    public function accountCompensatedTonnesOfEvent(string $accountId, float $tonnes, string $eventId): void
    {
        $projectRepository = $this->client->getContainer()->get(ProjectRepository::class);
        $project = ProjectMother::fromNameAndId('Reforestación', (string) ProjectId::generate());
        $projectRepository->add($project);

        $projectContribution = new ProjectContribution(id: ProjectContributionId::generate());
        $projectContribution->register(
            accountId: new AccountId($accountId),
            projectId: $project->id(),
            origin: ProjectContributionOrigin::EventCarbonFootprint,
            originId: $eventId,
            originName: 'Evento A',
            co2eqKg: $tonnes * 1000,
            tonnes: $tonnes,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            freeAmount: false,
            contributionAmount: 15.0,
            managementFeeAmount: 5.0,
            taxableAmount: 20.0,
            vatRate: 0.0,
            vatAmount: 0.0,
            totalAmount: 20.0,
            vatReason: null,
            currency: 'EUR',
            contributorName: 'MyCompany',
            contributorEmail: 'owner@dediez.software',
            contributorCountry: 'ES',
            contributorIsCompany: true,
            contributorCompanyName: 'MyCompany',
            contributorVatNumber: 'B12345678',
            locale: Locale::Spanish,
            expiresAt: null,
        );
        $projectContribution->pay();

        $this->client->getContainer()->get(ProjectContributionRepository::class)->add($projectContribution);
    }

    #[Then('the report of event carbon footprint :id should be :status keeping its file path')]
    public function theReportOfEventCarbonFootprintShouldBeKeepingItsFilePath(string $id, string $status): void
    {
        $report = $this->getEventCarbonFootprintReportRepository()->findByEventCarbonFootprintId(new EventCarbonFootprintId($id));

        Assert::assertNotNull($report);
        Assert::assertSame($status, $report->status()->value);
        Assert::assertSame(sprintf('%s/%s-es.pdf', $report->accountId(), $report->id()), $report->filePathFor(Locale::Spanish));
    }

    private function getEventCarbonFootprintReportRepository(): EventCarbonFootprintReportRepository
    {
        return $this->client->getContainer()->get(EventCarbonFootprintReportRepository::class);
    }

    private function getPublicLandingRepository(): PublicLandingRepository
    {
        return $this->client->getContainer()->get(PublicLandingRepository::class);
    }

    private function getTripCalculationLinkRepository(): TripCalculationLinkRepository
    {
        return $this->client->getContainer()->get(TripCalculationLinkRepository::class);
    }

    private function getTripCalculationRepository(): TripCalculationRepository
    {
        return $this->client->getContainer()->get(TripCalculationRepository::class);
    }
}
