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
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
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
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionTranslation;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;
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

    public function getPurchaseServiceRepository(): PurchaseServiceRepository
    {
        return $this->client->getContainer()->get(PurchaseServiceRepository::class);
    }

    public function getCapitalGoodRepository(): CapitalGoodRepository
    {
        return $this->client->getContainer()->get(CapitalGoodRepository::class);
    }

    public function getUseProfileRepository(): UseProfileRepository
    {
        return $this->client->getContainer()->get(UseProfileRepository::class);
    }

    public function getProcessRepository(): ProcessRepository
    {
        return $this->client->getContainer()->get(ProcessRepository::class);
    }

    public function getEconomicSectorRepository(): EconomicSectorRepository
    {
        return $this->client->getContainer()->get(EconomicSectorRepository::class);
    }

    public function getCateringItemRepository(): CateringItemRepository
    {
        return $this->client->getContainer()->get(CateringItemRepository::class);
    }

    public function getCombustibleRepository(): CombustibleRepository
    {
        return $this->client->getContainer()->get(CombustibleRepository::class);
    }

    public function getTransportRepository(): TransportRepository
    {
        return $this->client->getContainer()->get(TransportRepository::class);
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

    #[Given('purchase service :name with id :id exists')]
    public function purchaseServiceWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $purchaseService = PurchaseServiceMother::fromNameAndId($name, $id);

        $this->getPurchaseServiceRepository()->add($purchaseService);
    }

    #[Given('purchase service :name with id :id and emission factor for year :year exists')]
    public function purchaseServiceWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $purchaseService = PurchaseServiceMother::fromNameAndId($name, $id);

        $purchaseService->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $purchaseService->unit(),
            source: 'IPCC',
        ));

        $this->getPurchaseServiceRepository()->add($purchaseService);
    }

    #[Given('capital good :name with id :id exists')]
    public function capitalGoodWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $capitalGood = CapitalGoodMother::fromNameAndId($name, $id);

        $this->getCapitalGoodRepository()->add($capitalGood);
    }

    #[Given('capital good :name with id :id and emission factor for year :year exists')]
    public function capitalGoodWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $capitalGood = CapitalGoodMother::fromNameAndId($name, $id);

        $capitalGood->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $capitalGood->unit(),
            source: 'IPCC',
        ));

        $this->getCapitalGoodRepository()->add($capitalGood);
    }

    #[Given('use profile :name with id :id exists')]
    public function useProfileWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $useProfile = UseProfileMother::fromNameAndId($name, $id);

        $this->getUseProfileRepository()->add($useProfile);
    }

    #[Given('use profile :name with id :id and emission factor for year :year exists')]
    public function useProfileWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $useProfile = UseProfileMother::fromNameAndId($name, $id);

        $useProfile->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $useProfile->unit(),
            source: 'IPCC',
        ));

        $this->getUseProfileRepository()->add($useProfile);
    }

    #[Given('economic sector :name with id :id exists')]
    public function economicSectorWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $economicSector = EconomicSectorMother::fromNameAndId($name, $id);

        $this->getEconomicSectorRepository()->add($economicSector);
    }

    #[Given('economic sector :name with id :id and classification code :code exists')]
    public function economicSectorWithClassificationCodeExists(string $name, string $id, string $code): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $economicSector = EconomicSectorMother::from(name: $name, id: $id, classificationCode: $code);

        $this->getEconomicSectorRepository()->add($economicSector);
    }

    #[Given('economic sector :name with id :id and emission factor for year :year exists')]
    public function economicSectorWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $economicSector = EconomicSectorMother::fromNameAndId($name, $id);

        $economicSector->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $economicSector->unit(),
            source: 'IPCC',
        ));

        $this->getEconomicSectorRepository()->add($economicSector);
    }

    #[Given('process :name with id :id exists')]
    public function processWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $process = ProcessMother::fromNameAndId($name, $id);

        $this->getProcessRepository()->add($process);
    }

    #[Given('process :name with id :id and emission factor for year :year exists')]
    public function processWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $process = ProcessMother::fromNameAndId($name, $id);

        $process->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $process->unit(),
            source: 'IPCC',
        ));

        $this->getProcessRepository()->add($process);
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

    #[Given('combustible :name with id :id and emission factor with well-to-tank for year :year exists')]
    public function combustibleWithWellToTankEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $combustible = CombustibleMother::fromNameAndId($name, $id);

        $combustible->setEmissionFactor(
            new EmissionFactor(
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.2',
                n2o: '0.3',
                year: (int) $year,
                emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
                source: 'IPCC',
            ),
            $this->wellToTankEmissionFactor((int) $year),
        );

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Given('emission factor group :name with id :id exists')]
    public function emissionFactorGroupWithIdExists(string $name, string $id): void
    {
        $emissionFactorGroup = EmissionFactorGroupMother::fromNameAndId($name, $id);

        $this->getEmissionFactorGroupRepository()->add($emissionFactorGroup);
    }

    #[Given('transport :name with id :id exists')]
    public function transportWithIdExists(string $name, string $id): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $transport = TransportMother::fromNameAndId($name, $id);

        $this->getTransportRepository()->add($transport);
    }

    #[Given('transport :name with id :id and emission factor for year :year exists')]
    public function transportWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $transport = TransportMother::fromNameAndId($name, $id);

        $transport->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getTransportRepository()->add($transport);
    }

    #[Given('transport :name with id :id in unit :unit with vehicle :vehicle and emission factor for year :year exists')]
    public function transportWithUnitVehicleAndEmissionFactorExists(string $name, string $id, string $unit, string $vehicle, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $transport = TransportMother::from($name, $id, Unit::from($unit), Vehicle::from($vehicle));

        $transport->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: $transport->unit(),
            source: 'IPCC',
        ));

        $this->getTransportRepository()->add($transport);
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

    #[Given('electricity marketer :name with id :id and emission factor with well-to-tank for year :year exists')]
    public function electricityMarketerWithWellToTankEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMarketer = ElectricityMarketerMother::fromNameAndId($name, $id);

        $electricityMarketer->setEmissionFactor(
            new EmissionFactor(
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.2',
                n2o: '0.3',
                year: (int) $year,
                emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
                source: 'IPCC',
            ),
            $this->wellToTankEmissionFactor((int) $year),
        );

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

    #[Given('electricity mix for country :country with id :id and emission factor :co2eq for year :year exists')]
    public function electricityMixWithGivenEmissionFactorExists(string $country, string $id, string $co2eq, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMix = ElectricityMixMother::fromCountryAndId($country, $id);

        $electricityMix->setEmissionFactor(new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2eq,
            ch4: '0',
            n2o: '0',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'IPCC',
        ));

        $this->getElectricityMixRepository()->add($electricityMix);
    }

    #[Given('electricity mix for country :country with id :id and emission factor with well-to-tank for year :year exists')]
    public function electricityMixWithWellToTankEmissionFactorExists(string $country, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $electricityMix = ElectricityMixMother::fromCountryAndId($country, $id);

        $electricityMix->setEmissionFactor(
            new EmissionFactor(
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.2',
                n2o: '0.3',
                year: (int) $year,
                emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
                source: 'IPCC',
            ),
            $this->wellToTankEmissionFactor((int) $year),
        );

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

    #[Given('gdo factor :name with id :id and emission factor with well-to-tank for year :year exists')]
    public function gdOFactorWithWellToTankEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $gdOFactor = GdOFactorMother::fromNameAndId($name, $id);

        $gdOFactor->setEmissionFactor(
            new EmissionFactor(
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.2',
                n2o: '0.3',
                year: (int) $year,
                emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
                source: 'IPCC',
            ),
            $this->wellToTankEmissionFactor((int) $year),
        );

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

    #[Given('machinery fuel :name with id :id and emission factor with well-to-tank for year :year exists')]
    public function machineryFuelWithWellToTankEmissionFactorExists(string $name, string $id, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $machineryFuel = MachineryFuelMother::fromNameAndId($name, $id);

        $machineryFuel->setEmissionFactor(
            new EmissionFactor(
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.2',
                n2o: '0.3',
                year: (int) $year,
                emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
                source: 'IPCC',
            ),
            $this->wellToTankEmissionFactor((int) $year),
        );

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

        $eventCarbonFootprint->addActivity($response->activityId, new ActivityPlacement(Phase::Celebration, Source::AttendeesMobility));

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

        $eventCarbonFootprint->addActivity($response->activityId, new ActivityPlacement(Phase::Assembly, Source::WasteManagement));

        $this->getEventCarbonFootprintRepository()->add($eventCarbonFootprint);
    }

    #[Given('event carbon footprint :id has an electricity activity using marketer :marketerId of :quantity kWh in assembly for country :countryId')]
    public function eventCarbonFootprintHasAnElectricityActivityInAssemblyForCountry(string $id, string $marketerId, string $quantity, string $countryId): void
    {
        $this->addElectricityActivityInAssembly($id, $marketerId, $quantity, new CountryId($countryId));
    }

    #[Given('event carbon footprint :id has an electricity activity using marketer :marketerId of :quantity kWh in assembly')]
    public function eventCarbonFootprintHasAnElectricityActivityInAssembly(string $id, string $marketerId, string $quantity): void
    {
        $this->addElectricityActivityInAssembly($id, $marketerId, $quantity, null);
    }

    private function addElectricityActivityInAssembly(string $id, string $marketerId, string $quantity, ?CountryId $countryId): void
    {
        $eventCarbonFootprint = $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id));

        Assert::assertNotNull($eventCarbonFootprint);

        $accountId = $eventCarbonFootprint->accountId();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        Assert::assertNotNull($accountId);
        Assert::assertNotNull($emissionFactorGroupId);

        $registerElectricityActivity = $this->client->getContainer()->get(RegisterElectricityActivityService::class);

        $response = $registerElectricityActivity(
            new RegisterElectricityActivityRequest(
                accountId: $accountId,
                electricityMarketerId: new ElectricityMarketerId($marketerId),
                gdOFactorId: null,
                quantity: new Quantity((float) $quantity, Unit::KWh),
                year: $eventCarbonFootprint->calculationYear(),
                emissionFactorGroupId: $emissionFactorGroupId,
                source: null,
                countryId: $countryId,
            ),
            UserMother::adminUser(),
        );

        $eventCarbonFootprint->addActivity($response->activityId, new ActivityPlacement(Phase::Assembly, Source::BuildingElectricity));

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

    #[Given('property carbon footprint :id is completed')]
    public function propertyCarbonFootprintIsCompleted(string $id): void
    {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintRepository()->find(new PropertyCarbonFootprintId($id));

        Assert::assertNotNull($propertyCarbonFootprint);

        $propertyCarbonFootprint->complete(new Emissions());

        $this->getPropertyCarbonFootprintRepository()->add($propertyCarbonFootprint);
    }

    #[Given('company carbon footprint :id is completed')]
    public function companyCarbonFootprintIsCompleted(string $id): void
    {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id));

        Assert::assertNotNull($companyCarbonFootprint);

        $companyCarbonFootprint->complete(new Emissions());

        $this->getCompanyCarbonFootprintRepository()->add($companyCarbonFootprint);
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

    #[Given('trip calculation :id of link :linkId exists')]
    public function tripCalculationOfLinkExists(string $id, string $linkId): void
    {
        $this->getTripCalculationRepository()->add(TripCalculationMother::from(tripCalculationLinkId: $linkId, id: $id));
    }

    #[Given('trip calculation link with id :id and token :token for property :propertyId of account :account with scope :scope exists')]
    public function tripCalculationLinkWithIdAndTokenForPropertyOfAccountWithScopeExists(string $id, string $token, string $propertyId, string $account, string $scope): void
    {
        $this->tripCalculationLinkWithIdAndTokenForPropertyOfAccountExists($id, $token, $propertyId, $account);
        $this->tripCalculationLinkHasScope($id, $scope);
    }

    #[Given('account trip calculator with id :id and token :token of account :account exists')]
    public function accountTripCalculatorWithIdAndTokenOfAccountExists(string $id, string $token, string $account): void
    {
        $tripCalculationLink = new TripCalculationLink(new TripCalculationLinkId($id));
        $tripCalculationLink->registerForAccount(
            accountId: new AccountId($account),
            token: $token,
        );

        $this->getTripCalculationLinkRepository()->add($tripCalculationLink);
    }

    #[Given('account trip calculator :id allows destinations in countries :isos')]
    public function accountTripCalculatorAllowsDestinationsInCountries(string $id, string $isos): void
    {
        $countryRepository = $this->client->getContainer()->get(CountryRepository::class);
        $countryIds = [];

        foreach (array_filter(array_map('trim', explode(',', $isos))) as $iso) {
            $country = $countryRepository->findByIso($iso);

            Assert::assertNotNull($country);

            $countryIds[] = $country->id();
        }

        $this->updateAccountTripCalculatorDestination($id, TripDestinationMode::Countries, countryIds: $countryIds);
    }

    #[Given('account trip calculator :id allows destinations in region :reference')]
    public function accountTripCalculatorAllowsDestinationsInRegion(string $id, string $reference): void
    {
        $region = $this->client->getContainer()->get(PlaceFinder::class)->resolve($reference, Locale::Spanish);

        Assert::assertNotNull($region);
        Assert::assertNotNull($region->viewport);

        $this->updateAccountTripCalculatorDestination(
            $id,
            TripDestinationMode::Region,
            regionReference: $reference,
            regionLabel: $region->name ?? $region->label,
            regionBounds: $region->viewport,
        );
    }

    #[Given('account trip calculator :id allows destinations in all properties')]
    public function accountTripCalculatorAllowsDestinationsInAllProperties(string $id): void
    {
        $this->updateAccountTripCalculatorDestination($id, TripDestinationMode::AllProperties);
    }

    #[Given('account trip calculator :id allows destinations in properties :ids')]
    public function accountTripCalculatorAllowsDestinationsInProperties(string $id, string $ids): void
    {
        $propertyIds = array_map(
            static fn(string $propertyId): PropertyId => new PropertyId($propertyId),
            array_values(array_filter(array_map('trim', explode(',', $ids)))),
        );

        $this->updateAccountTripCalculatorDestination($id, TripDestinationMode::SelectedProperties, propertyIds: $propertyIds);
    }

    #[Then('trip calculation of link :id should have destination :label')]
    public function tripCalculationOfLinkShouldHaveDestination(string $id, string $label): void
    {
        $tripCalculations = $this->getTripCalculationRepository()->findByLinkId(new TripCalculationLinkId($id));

        Assert::assertCount(1, $tripCalculations);
        Assert::assertSame($label, $tripCalculations[0]->destinationLabel());
    }

    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    private function updateAccountTripCalculatorDestination(
        string $id,
        TripDestinationMode $mode,
        array $countryIds = [],
        array $propertyIds = [],
        ?string $regionReference = null,
        ?string $regionLabel = null,
        ?GeoBounds $regionBounds = null,
    ): void {
        $tripCalculationLink = $this->getTripCalculationLinkRepository()->find(new TripCalculationLinkId($id));

        Assert::assertNotNull($tripCalculationLink);

        $tripCalculationLink->updateDestination(
            mode: $mode,
            placeKind: $tripCalculationLink->destinationPlaceKind(),
            countryIds: $countryIds,
            propertyIds: $propertyIds,
            regionReference: $regionReference,
            regionLabel: $regionLabel,
            regionBounds: $regionBounds,
        );

        $this->getTripCalculationLinkRepository()->add($tripCalculationLink);
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

    #[Given('property :id is in country :countryId')]
    public function propertyIsInCountry(string $id, string $countryId): void
    {
        $propertyRepository = $this->client->getContainer()->get(PropertyRepository::class);
        $property = $propertyRepository->find(new PropertyId($id));

        Assert::assertNotNull($property);

        $property->update(
            name: $property->name(),
            accountId: $property->accountId(),
            info: new PropertyInfo(countryId: new CountryId($countryId)),
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

    #[Given(':subjectType carbon footprint :id has a report :status requested by :username')]
    public function carbonFootprintHasAReportRequestedBy(string $subjectType, string $id, string $status, string $username): void
    {
        $user = $this->client->getContainer()->get(UserRepository::class)->findByUsername($username);

        Assert::assertNotNull($user);

        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $accountId = $carbonFootprint->accountId();

        Assert::assertNotNull($accountId);

        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $reportId = new CarbonFootprintReportId($id);

        $report = match ($status) {
            'generating_texts' => CarbonFootprintReportMother::registeredFor($type, $id, $accountId, $user->id(), $reportId),
            'draft' => CarbonFootprintReportMother::draft($type, $id, $accountId, $user->id(), $reportId),
            'pending_review' => CarbonFootprintReportMother::pendingReview($type, $id, $accountId, $user->id(), $reportId),
            'generating_pdf' => CarbonFootprintReportMother::generatingPdf($type, $id, $accountId, $user->id(), $reportId),
            'generated' => $this->generatedReport($type, $id, $accountId, $user->id()),
            'failed' => CarbonFootprintReportMother::failedBeforeApproval($type, $id, $accountId, $user->id(), $reportId),
            default => throw new \InvalidArgumentException(sprintf('Unknown report status "%s"', $status)),
        };

        $this->getCarbonFootprintReportRepository()->add($report);
    }

    #[Then('the report of :subjectType carbon footprint :id should be :status')]
    public function theReportOfCarbonFootprintShouldBe(string $subjectType, string $id, string $status): void
    {
        Assert::assertSame($status, $this->findReport($subjectType, $id)->status()->value);
    }

    #[Then('the report of :subjectType carbon footprint :id should be :status keeping its file path')]
    public function theReportOfCarbonFootprintShouldBeKeepingItsFilePath(string $subjectType, string $id, string $status): void
    {
        $report = $this->findReport($subjectType, $id);

        Assert::assertSame($status, $report->status()->value);
        Assert::assertSame(sprintf('%s/%s-es.pdf', $report->accountId(), $report->id()), $report->filePathFor(Locale::Spanish));
    }

    #[Then('there should be no report of :subjectType carbon footprint :id')]
    public function thereShouldBeNoReportOfCarbonFootprint(string $subjectType, string $id): void
    {
        Assert::assertNull($this->getCarbonFootprintReportRepository()->findBySubject(CarbonFootprintReportSubjectType::from($subjectType), $id));
    }

    #[Then('the report of :subjectType carbon footprint :id should have section :key with :text')]
    public function theReportOfCarbonFootprintShouldHaveSectionWith(string $subjectType, string $id, string $key, string $text): void
    {
        Assert::assertSame($text, $this->findReport($subjectType, $id)->texts()->get(CarbonFootprintReportSectionKey::from($key)));
    }

    private function generatedReport(CarbonFootprintReportSubjectType $type, string $id, AccountId $accountId, UserId $userId): CarbonFootprintReport
    {
        $report = CarbonFootprintReportMother::generatingPdf($type, $id, $accountId, $userId, new CarbonFootprintReportId($id));
        $report->translate(sprintf('%s/%s-es.pdf', $accountId, $report->id()), Locale::Spanish);
        $report->generate();

        return $report;
    }

    private function findReport(string $subjectType, string $id): CarbonFootprintReport
    {
        $report = $this->getCarbonFootprintReportRepository()->findBySubject(CarbonFootprintReportSubjectType::from($subjectType), $id);

        Assert::assertNotNull($report);

        return $report;
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

    #[When('I calculate emissions for event :eventId with type :type, item :itemId, quantity :quantity and unit :unit')]
    public function iCalculateEmissionsForEvent(string $eventId, string $type, string $itemId, string $quantity, string $unit): void
    {
        $this->client->request(
            'POST',
            sprintf('/es/events/%s/carbon-footprint/emissions/calculate', $eventId),
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['type' => $type, 'itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit], JSON_THROW_ON_ERROR),
        );
    }

    #[Then('the calculated co2eq emissions should be :value')]
    public function theCalculatedCo2EqEmissionsShouldBe(string $value): void
    {
        $response = $this->client->getResponse()->getContent();

        Assert::assertNotFalse($response);

        $json = json_decode($response, true);

        Assert::assertIsArray($json);
        Assert::assertArrayHasKey('emissions', $json);
        Assert::assertEqualsWithDelta((float) $value, (float) $json['emissions']['co2eq'], 0.0001);
    }

    private function getCarbonFootprintReportRepository(): CarbonFootprintReportRepository
    {
        return $this->client->getContainer()->get(CarbonFootprintReportRepository::class);
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

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year exists')]
    public function propertyCarbonFootprintExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::from($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with scope 1 answered exists')]
    public function propertyCarbonFootprintWithScope1AnsweredExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::withScope1Answered($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with scope 2 answered exists')]
    public function propertyCarbonFootprintWithScope2AnsweredExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::withScope2Answered($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year exists')]
    public function companyCarbonFootprintExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getCompanyCarbonFootprintRepository()->add(
            CompanyCarbonFootprintMother::from($companyId, $id, $account, (int) $year),
        );
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year with scope 1 answered exists')]
    public function companyCarbonFootprintWithScope1AnsweredExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getCompanyCarbonFootprintRepository()->add(
            CompanyCarbonFootprintMother::withScope1Answered($companyId, $id, $account, (int) $year),
        );
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year with scope 2 answered exists')]
    public function companyCarbonFootprintWithScope2AnsweredExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getCompanyCarbonFootprintRepository()->add(
            CompanyCarbonFootprintMother::withScope2Answered($companyId, $id, $account, (int) $year),
        );
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with scope 3 answered exists')]
    public function propertyCarbonFootprintWithScope3AnsweredExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::withScope3Answered($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with scope 3 excluded exists')]
    public function propertyCarbonFootprintWithScope3ExcludedExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::withScope3Excluded($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year with scope 3 answered exists')]
    public function companyCarbonFootprintWithScope3AnsweredExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getCompanyCarbonFootprintRepository()->add(
            CompanyCarbonFootprintMother::withScope3Answered($companyId, $id, $account, (int) $year),
        );
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with scope 3 answered and later excluded exists')]
    public function propertyCarbonFootprintWithScope3AnsweredAndLaterExcludedExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $propertyCarbonFootprint = PropertyCarbonFootprintMother::withScope3Answered($propertyId, $id, $account, (int) $year);
        $propertyCarbonFootprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->getPropertyCarbonFootprintRepository()->add($propertyCarbonFootprint);
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year with scope 3 answered and later excluded exists')]
    public function companyCarbonFootprintWithScope3AnsweredAndLaterExcludedExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $companyCarbonFootprint = CompanyCarbonFootprintMother::withScope3Answered($companyId, $id, $account, (int) $year);
        $companyCarbonFootprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->getCompanyCarbonFootprintRepository()->add($companyCarbonFootprint);
    }

    #[Given('property carbon footprint with id :id for property :propertyId of account :account and year :year with providers answered exists')]
    public function propertyCarbonFootprintWithProvidersAnsweredExists(string $id, string $propertyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getPropertyCarbonFootprintRepository()->add(
            PropertyCarbonFootprintMother::withProvidersAnswered($propertyId, $id, $account, (int) $year),
        );
    }

    #[Given('company carbon footprint with id :id for company :companyId of account :account and year :year with providers answered exists')]
    public function companyCarbonFootprintWithProvidersAnsweredExists(string $id, string $companyId, string $account, string $year): void
    {
        $this->ensureDefaultEmissionFactorGroupExists();

        $this->getCompanyCarbonFootprintRepository()->add(
            CompanyCarbonFootprintMother::withProvidersAnswered($companyId, $id, $account, (int) $year),
        );
    }

    #[Given(':subjectType carbon footprint :id has a purchase service activity :itemId in scope 3')]
    #[Given(':subjectType carbon footprint :id has a purchase service activity :itemId in scope 3 for property :propertyId')]
    public function carbonFootprintHasAPurchaseServiceActivityInScope3(string $subjectType, string $id, string $itemId, ?string $propertyId = null): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $accountId = $carbonFootprint->accountId();
        Assert::assertNotNull($accountId);

        $activity = PurchaseServiceActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId,
            purchaseServiceId: new PurchaseServiceId($itemId),
            emissionFactor: new EmissionFactor('0.5', '0.5', '0', '0', $carbonFootprint->calculationYear(), EmissionFactorGroupMother::default()->id(), Unit::Euro, 'EXIOBASE'),
            emissions: new Emissions('500', '500', '0', '0'),
            quantity: new Quantity(1000.0, Unit::Euro),
        );
        $this->getActivityRepository()->save($activity);

        $carbonFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, propertyId: null !== $propertyId ? new PropertyId($propertyId) : null));

        match ($subjectType) {
            'property' => $this->getPropertyCarbonFootprintRepository()->add($this->propertyCarbonFootprint($id)),
            default => $this->getCompanyCarbonFootprintRepository()->add($this->companyCarbonFootprint($id)),
        };
    }

    #[Given(':subjectType carbon footprint :id has a combustible :combustibleId activity of :co2eq kg with :wellToTank kg well-to-tank')]
    public function carbonFootprintHasACombustibleActivityWithWellToTank(string $subjectType, string $id, string $combustibleId, string $co2eq, string $wellToTank): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $accountId = $carbonFootprint->accountId();
        Assert::assertNotNull($accountId);

        $activity = CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId,
            combustibleId: new CombustibleId($combustibleId),
            emissionFactor: new EmissionFactor('1.5', '1.0', '0.2', '0.3', $carbonFootprint->calculationYear(), EmissionFactorGroupMother::default()->id(), Unit::Liter, 'IPCC'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(100.0, Unit::Liter),
            wellToTankEmissionFactor: new EmissionFactor('0.5', '0.5', '0', '0', $carbonFootprint->calculationYear(), EmissionFactorGroupMother::default()->id(), Unit::Liter, 'DEFRA'),
            wellToTankEmissions: new Emissions($wellToTank, $wellToTank, '0', '0'),
        );
        $this->getActivityRepository()->save($activity);

        $carbonFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        match ($subjectType) {
            'property' => $this->getPropertyCarbonFootprintRepository()->add($this->propertyCarbonFootprint($id)),
            default => $this->getCompanyCarbonFootprintRepository()->add($this->companyCarbonFootprint($id)),
        };
    }

    #[Then('mitigation measure :id should reduce source :source')]
    public function mitigationMeasureShouldReduceSource(string $id, string $source): void
    {
        $measure = $this->getMitigationMeasureRepository()->find(new MitigationMeasureId($id));

        Assert::assertNotNull($measure);
        Assert::assertTrue($measure->effects()->hasForSource(Source::from($source)));
    }

    #[Then(':subjectType carbon footprint :id should have total emissions of :co2eq kg')]
    public function carbonFootprintShouldHaveTotalEmissions(string $subjectType, string $id, string $co2eq): void
    {
        $carbonFootprint = 'property' === $subjectType ? $this->propertyCarbonFootprint($id) : $this->companyCarbonFootprint($id);

        Assert::assertEqualsWithDelta((float) $co2eq, (float) $carbonFootprint->totalEmissions()->co2eq(), 0.0001);
    }

    #[When('I submit the scope 3 step form excluding the whole scope with reason :reason')]
    public function iSubmitTheScope3StepFormExcludingTheWholeScope(string $reason): void
    {
        $this->submitScopeStep(['stageExclusion' => ['excluded' => '1', 'reason' => $reason, 'comment' => '']]);
    }

    #[When('I submit the scope 3 step form with a purchase service :itemId of :quantity :unit')]
    public function iSubmitTheScope3StepFormWithAPurchaseService(string $itemId, string $quantity, string $unit): void
    {
        $this->submitScopeStepRow('purchasedGoodsAndServices', ['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]);
    }

    #[When('I submit the scope 3 step form with an investment :itemId of :quantity :unit')]
    public function iSubmitTheScope3StepFormWithAnInvestment(string $itemId, string $quantity, string $unit): void
    {
        $this->submitScopeStepRow('investments', ['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]);
    }

    #[When('I submit the scope 3 step form with a business trip :mobilityId of :km km and a stay :overnightStayId of :nights nights for :people people')]
    public function iSubmitTheScope3StepFormWithABusinessTripAndAStay(string $mobilityId, string $km, string $overnightStayId, string $nights, string $people): void
    {
        $this->submitScopeStep([
            'businessTravel' => [['itemId' => $mobilityId, 'quantity' => $km, 'unit' => Unit::Kilometer->value]],
            'businessTravelStays' => [['itemId' => $overnightStayId, 'nights' => $nights, 'people' => $people, 'unit' => Unit::Pernoctacion->value]],
        ]);
    }

    #[When('I submit the company scope 3 step form with a purchase service :itemId of :quantity :unit for property :propertyId')]
    public function iSubmitTheCompanyScope3StepFormWithAPurchaseServiceForProperty(string $itemId, string $quantity, string $unit, string $propertyId): void
    {
        $row = ['purchasedGoodsAndServices' => [['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]]];

        $this->submitScopeStep('none' === $propertyId ? ['unassigned' => $row] : ['properties' => [$propertyId => $row]]);
    }

    #[When('I submit the company scope 3 step form with manual emissions :co2eq for property :propertyId')]
    public function iSubmitTheCompanyScope3StepFormWithManualEmissionsForProperty(string $co2eq, string $propertyId): void
    {
        $this->submitScopeStep(['nonConsolidated' => [$propertyId => ['co2eq' => $co2eq]]]);
    }

    #[Then('company carbon footprint :id should have :co2eq kg in source :source')]
    public function companyCarbonFootprintShouldHaveEmissionsInSource(string $id, string $co2eq, string $source): void
    {
        /** @var EmissionsBreakdownBuilder $breakdownBuilder */
        $breakdownBuilder = $this->client->getContainer()->get(EmissionsBreakdownBuilder::class);

        $breakdown = $breakdownBuilder->build($this->companyCarbonFootprint($id), Scope::cases());

        Assert::assertEqualsWithDelta((float) $co2eq, (float) $breakdown->bySource[Source::from($source)->value]->co2eq(), 0.0001);
    }

    #[Then(':subjectType carbon footprint :id should have status :status')]
    public function carbonFootprintShouldHaveStatus(string $subjectType, string $id, string $status): void
    {
        $carbonFootprint = 'property' === $subjectType ? $this->propertyCarbonFootprint($id) : $this->companyCarbonFootprint($id);

        Assert::assertSame(OrganizationalCarbonFootprintStatus::from($status), $carbonFootprint->status());
    }

    #[Then(':subjectType carbon footprint :id should have scope 3 excluded with reason :reason')]
    public function carbonFootprintShouldHaveScope3ExcludedWithReason(string $subjectType, string $id, string $reason): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);

        Assert::assertTrue($carbonFootprint->isStageExcluded(Scope::Scope3));
        Assert::assertSame(ExclusionReason::from($reason), $carbonFootprint->stageExclusionReason(Scope::Scope3));
    }

    #[Then(':subjectType carbon footprint :id should have :count activities in scope 3')]
    public function carbonFootprintShouldHaveActivitiesInScope3(string $subjectType, string $id, int $count): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);

        Assert::assertCount($count, array_filter(
            $carbonFootprint->activityReferences(),
            static fn(ActivityReference $reference): bool => Scope::Scope3->key() === $reference->placement->stage->key(),
        ));
    }

    /** @param array<string, mixed> $parameters */
    private function submitScopeStep(array $parameters): void
    {
        $token = $this->client->getCrawler()->filter('input[name="_token"]')->attr('value');
        Assert::assertNotNull($token);

        $uri = $this->client->getRequest()->getUri();

        $this->client->requestWithFiles('POST', $uri, ['_token' => $token] + $parameters, [], ['HTTP_REFERER' => $uri]);
    }

    #[When('I submit the scope :scope step form excluding source :source with reason :reason and comment :comment')]
    public function iSubmitTheScopeStepFormExcludingSource(string $scope, string $source, string $reason, string $comment): void
    {
        $scopeEnum = Scope::from($scope);
        $sourceEnum = Source::from($source);

        $form = $this->client->getCrawler()->filter('form')->form();

        foreach ($scopeEnum->sections() as $section) {
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

    #[When('I submit the scope :scope step form with a combustible :itemId of :quantity :unit')]
    public function iSubmitTheScopeStepFormWithACombustible(string $scope, string $itemId, string $quantity, string $unit): void
    {
        $this->submitScopeStepRow('combustibles', ['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]);
    }

    #[When('I submit the scope :scope step form with an electricity marketer :itemId of :quantity :unit')]
    public function iSubmitTheScopeStepFormWithAnElectricityMarketer(string $scope, string $itemId, string $quantity, string $unit): void
    {
        $this->submitScopeStepRow('electricity', ['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]);
    }

    /** @param array<string, string> $row */
    private function submitScopeStepRow(string $sectionKey, array $row): void
    {
        $token = $this->client->getCrawler()->filter('input[name="_token"]')->attr('value');
        Assert::assertNotNull($token);

        $uri = $this->client->getRequest()->getUri();

        $this->client->requestWithFiles('POST', $uri, ['_token' => $token, $sectionKey => [$row]], [], ['HTTP_REFERER' => $uri]);
    }

    #[When('I submit the company scope :scope step form with a combustible :itemId of :quantity :unit for property :propertyId')]
    public function iSubmitTheCompanyScopeStepFormWithACombustibleForProperty(string $scope, string $itemId, string $quantity, string $unit, string $propertyId): void
    {
        $crawler = $this->client->getCrawler();

        $token = $crawler->filter('input[name="_token"]')->attr('value');
        Assert::assertNotNull($token);

        $row = ['combustibles' => [['itemId' => $itemId, 'quantity' => $quantity, 'unit' => $unit]]];

        $parameters = ['_token' => $token]
            + ('none' === $propertyId ? ['unassigned' => $row] : ['properties' => [$propertyId => $row]]);

        $uri = $this->client->getRequest()->getUri();

        $this->client->requestWithFiles('POST', $uri, $parameters, [], ['HTTP_REFERER' => $uri]);
    }

    #[When('I submit the company scope :scope step form with a transport :itemId of :weight :weightUnit over :distanceKm km for property :propertyId')]
    public function iSubmitTheCompanyScopeStepFormWithATransportForProperty(string $scope, string $itemId, string $weight, string $weightUnit, string $distanceKm, string $propertyId): void
    {
        $crawler = $this->client->getCrawler();

        $token = $crawler->filter('input[name="_token"]')->attr('value');
        Assert::assertNotNull($token);

        $row = ['vehicles' => [['itemId' => $itemId, 'weight' => $weight, 'weightUnit' => $weightUnit, 'distanceKm' => $distanceKm]]];

        $parameters = ['_token' => $token]
            + ('none' === $propertyId ? ['unassigned' => $row] : ['properties' => [$propertyId => $row]]);

        $uri = $this->client->getRequest()->getUri();

        $this->client->requestWithFiles('POST', $uri, $parameters, [], ['HTTP_REFERER' => $uri]);
    }

    #[Then('company carbon footprint :id activity of transport :transportId has quantity :quantity :unit')]
    public function companyCarbonFootprintActivityOfTransportHasQuantity(string $id, string $transportId, float $quantity, string $unit): void
    {
        $footprint = $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id));

        Assert::assertNotNull($footprint);

        $activities = array_filter(
            $this->getActivityRepository()->findByIds(array_map(
                static fn(ActivityReference $reference): ActivityId => $reference->activityId,
                $footprint->activityReferences(),
            )),
            static fn(Activity $activity): bool => $activity instanceof TransportActivity && (string) $activity->transportId() === $transportId,
        );

        Assert::assertCount(1, $activities);

        $activity = array_values($activities)[0];
        Assert::assertInstanceOf(TransportActivity::class, $activity);
        Assert::assertEqualsWithDelta($quantity, $activity->quantity()->value(), 0.0001);
        Assert::assertSame(Unit::from($unit), $activity->quantity()->unit());
        Assert::assertNotNull($activity->weight());
        Assert::assertNotNull($activity->distanceKm());
    }

    #[Then('property carbon footprint :id should have source :source excluded in scope :scope')]
    public function propertyCarbonFootprintShouldHaveSourceExcludedInScope(string $id, string $source, string $scope): void
    {
        $footprint = $this->getPropertyCarbonFootprintRepository()->find(new PropertyCarbonFootprintId($id));

        Assert::assertNotNull($footprint);
        Assert::assertTrue($footprint->isSourceExcluded(Scope::from($scope), Source::from($source)));
    }

    #[Then('property carbon footprint :id should have :count activities of source :source in scope :scope')]
    public function propertyCarbonFootprintShouldHaveActivitiesOfSourceInScope(string $id, int $count, string $source, string $scope): void
    {
        $footprint = $this->getPropertyCarbonFootprintRepository()->find(new PropertyCarbonFootprintId($id));

        Assert::assertNotNull($footprint);
        Assert::assertCount($count, $footprint->activityIds(new ActivityPlacement(Scope::from($scope), Source::from($source))));
    }

    #[Then('company carbon footprint :id should have :count activities of source :source in scope :scope for property :propertyId')]
    public function companyCarbonFootprintShouldHaveActivitiesOfSourceInScopeForProperty(string $id, int $count, string $source, string $scope, string $propertyId): void
    {
        $footprint = $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id));

        Assert::assertNotNull($footprint);
        Assert::assertCount($count, $footprint->activityIds(new ActivityPlacement(
            Scope::from($scope),
            Source::from($source),
            propertyId: 'none' === $propertyId ? null : new PropertyId($propertyId),
        )));
    }

    #[Then(':subjectType carbon footprint :id should be completed')]
    public function carbonFootprintShouldBeCompleted(string $subjectType, string $id): void
    {
        Assert::assertTrue($this->findCarbonFootprint($subjectType, $id)->isCompleted());
    }

    #[Then(':subjectType carbon footprint :id should not be completed')]
    public function carbonFootprintShouldNotBeCompleted(string $subjectType, string $id): void
    {
        Assert::assertFalse($this->findCarbonFootprint($subjectType, $id)->isCompleted());
    }

    #[Then('the combustible activity of :subjectType carbon footprint :id has well-to-tank emissions of :value kg co2eq')]
    public function theCombustibleActivityHasWellToTankEmissions(string $subjectType, string $id, float $value): void
    {
        $activity = $this->singleActivityOf($subjectType, $id, CombustibleActivity::class);

        Assert::assertEqualsWithDelta($value, (float) $activity->wellToTankEmissions()->co2eq(), 0.0001);
    }

    #[Then('the electricity activity of :subjectType carbon footprint :id has well-to-tank emissions of :value kg co2eq')]
    public function theElectricityActivityHasWellToTankEmissions(string $subjectType, string $id, float $value): void
    {
        $activity = $this->singleActivityOf($subjectType, $id, ElectricityActivity::class);

        Assert::assertEqualsWithDelta($value, (float) $activity->wellToTankEmissions()->co2eq(), 0.0001);
    }

    #[Then('the electricity activity of :subjectType carbon footprint :id has location-based well-to-tank emissions of :value kg co2eq')]
    public function theElectricityActivityHasLocationBasedWellToTankEmissions(string $subjectType, string $id, float $value): void
    {
        $activity = $this->singleActivityOf($subjectType, $id, ElectricityActivity::class);

        $locationBasedWellToTankEmissions = $activity->locationBasedWellToTankEmissions();
        Assert::assertNotNull($locationBasedWellToTankEmissions);
        Assert::assertEqualsWithDelta($value, (float) $locationBasedWellToTankEmissions->co2eq(), 0.0001);
    }

    /**
     * @template T of Activity
     *
     * @param class-string<T> $activityClass
     *
     * @return T
     */
    private function singleActivityOf(string $subjectType, string $id, string $activityClass): Activity
    {
        $activities = array_values(array_filter(
            $this->getActivityRepository()->findByIds(array_map(
                static fn(ActivityReference $reference): ActivityId => $reference->activityId,
                $this->findCarbonFootprint($subjectType, $id)->activityReferences(),
            )),
            static fn(Activity $activity): bool => $activity instanceof $activityClass,
        ));

        Assert::assertCount(1, $activities);
        Assert::assertInstanceOf($activityClass, $activities[0]);

        return $activities[0];
    }

    private function wellToTankEmissionFactor(int $year): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: '0.61',
            co2: '0.6',
            ch4: '0.01',
            n2o: '0.01',
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            source: 'DEFRA',
        );
    }

    #[Given('organizational provider contribution with id :id for :subjectType carbon footprint :footprintId and provider :providerId of account :account in mode :mode with token :token covering :sources exists')]
    public function organizationalProviderContributionExists(string $id, string $subjectType, string $footprintId, string $providerId, string $account, string $mode, string $token, string $sources): void
    {
        $this->registerOrganizationalProviderContribution($id, $subjectType, $footprintId, $providerId, $account, $mode, $token, $sources, null);
    }

    #[Given('organizational provider contribution with id :id for company carbon footprint :footprintId and provider :providerId of account :account in mode :mode with token :token covering :sources for property :propertyId exists')]
    public function organizationalProviderContributionForPropertyExists(string $id, string $footprintId, string $providerId, string $account, string $mode, string $token, string $sources, string $propertyId): void
    {
        $this->registerOrganizationalProviderContribution($id, 'company', $footprintId, $providerId, $account, $mode, $token, $sources, new PropertyId($propertyId));
    }

    #[Given('organizational provider contribution :id is ignored')]
    public function organizationalProviderContributionIsIgnored(string $id): void
    {
        $contribution = $this->getOrganizationalProviderContributionRepository()->find(new OrganizationalProviderContributionId($id));

        Assert::assertNotNull($contribution);

        $contribution->ignore();

        $this->getOrganizationalProviderContributionRepository()->add($contribution);
    }

    #[Given('organizational provider contribution :id has consent')]
    public function organizationalProviderContributionHasConsent(string $id): void
    {
        $contribution = $this->getOrganizationalProviderContributionRepository()->find(new OrganizationalProviderContributionId($id));

        Assert::assertNotNull($contribution);

        $contribution->grantConsent(ConsentProofMother::granted());

        $this->getOrganizationalProviderContributionRepository()->add($contribution);
    }

    #[When('I save the provider contribution form with reported emissions :rows in source :source')]
    public function iSaveTheProviderContributionFormWithReportedEmissions(string $rows, string $source): void
    {
        $this->saveProviderContributionForm($rows, $source, null);
    }

    #[When('I save the provider contribution form with categories :sources and reported emissions :rows in source :source')]
    public function iSaveTheProviderContributionFormWithCategoriesAndReportedEmissions(string $sources, string $rows, string $source): void
    {
        $this->saveProviderContributionForm($rows, $source, 'none' === $sources ? [] : array_map(static fn(string $value): Source => Source::from(trim($value)), explode(',', $sources)));
    }

    /** @param list<Source>|null $sources */
    private function saveProviderContributionForm(string $rows, string $source, ?array $sources): void
    {
        $form = $this->client->getCrawler()->filter('button[name$="[save]"]')->form();

        $reported = [];
        foreach (explode(',', $rows) as $row) {
            [$description, $quantity, $unit] = explode(':', $row);
            $reported[] = ['description' => $description, 'quantity' => $quantity, 'unit' => $unit, 'activityId' => ''];
        }

        $parameters = $form->getPhpValues();
        $parameters['reported'][$source] = $reported;

        if (null !== $sources) {
            $choices = [];
            foreach (Scope::Scope3->providerSources() as $index => $choice) {
                if (in_array($choice, $sources, true)) {
                    $choices[$index] = $choice->value;
                }
            }
            $parameters['change_organizational_provider_contribution_sources']['sources'] = $choices;
        }

        $this->client->requestWithFiles($form->getMethod(), $form->getUri(), $parameters, [], ['HTTP_REFERER' => $form->getUri()]);
    }

    #[When('I submit the provider emissions form in mode :mode for source :source with rows :rows')]
    public function iSubmitTheProviderEmissionsForm(string $mode, string $source, string $rows): void
    {
        $this->submitScopeStep($this->providerEmissionsParameters($mode, $source, $rows));
    }

    #[When('I post the provider emissions of token :token in mode :mode for source :source with rows :rows')]
    public function iPostTheProviderEmissionsOfToken(string $token, string $mode, string $source, string $rows): void
    {
        $csrfToken = 'acceptance-csrf-token';
        $this->client->setSessionValue('_csrf/provider_contribution_emissions_' . $token, $csrfToken);

        $this->client->requestWithFiles(
            'POST',
            '/es/carbon-footprint/provider-contribution/' . $token . '/emissions',
            ['_token' => $csrfToken] + $this->providerEmissionsParameters($mode, $source, $rows),
        );
    }

    /** @return array<string, mixed> */
    private function providerEmissionsParameters(string $mode, string $source, string $rows): array
    {
        $sourceEnum = Source::from($source);
        $sectionKey = 'reported' === $mode
            ? Scope::Scope3->reportedSectionFor($sourceEnum)->key
            : array_values(array_filter(Scope::Scope3->sections(), static fn($section): bool => $section->source === $sourceEnum))[0]->key;

        $answers = [];
        foreach (explode(',', $rows) as $row) {
            [$field, $quantity, $unit] = explode(':', $row);
            $answers[] = ('reported' === $mode ? ['description' => $field] : ['itemId' => $field]) + ['quantity' => $quantity, 'unit' => $unit, 'activityId' => ''];
        }

        return ['mode' => [$source => $mode], $sectionKey => $answers];
    }

    #[When('I submit the provider emissions form without choosing the mode for source :source with rows :rows')]
    public function iSubmitTheProviderEmissionsFormWithoutMode(string $source, string $rows): void
    {
        $answers = [];
        foreach (explode(',', $rows) as $row) {
            [$description, $quantity, $unit] = explode(':', $row);
            $answers[] = ['description' => $description, 'quantity' => $quantity, 'unit' => $unit, 'activityId' => ''];
        }

        $this->submitScopeStep([Scope::Scope3->reportedSectionFor(Source::from($source))->key => $answers]);
    }

    #[Then('organizational provider contribution :id should have status :status')]
    public function organizationalProviderContributionShouldHaveStatus(string $id, string $status): void
    {
        $contribution = $this->getOrganizationalProviderContributionRepository()->find(new OrganizationalProviderContributionId($id));

        Assert::assertNotNull($contribution);
        Assert::assertSame($status, $contribution->status()->value);
    }

    #[Then('organizational provider contribution :id should cover :sources')]
    public function organizationalProviderContributionShouldCover(string $id, string $sources): void
    {
        $contribution = $this->getOrganizationalProviderContributionRepository()->find(new OrganizationalProviderContributionId($id));

        Assert::assertNotNull($contribution);
        Assert::assertSame(
            array_map(static fn(string $source): string => trim($source), explode(',', $sources)),
            array_map(static fn(Source $source): string => $source->value, $contribution->sources()),
        );
    }

    #[Then('the previous step link should point to :href')]
    public function thePreviousStepLinkShouldPointTo(string $href): void
    {
        $links = $this->client->getCrawler()->selectLink('Anterior');

        Assert::assertCount(1, $links);
        Assert::assertSame($href, $links->attr('href'));
    }

    #[Then('organizational provider contribution :id should be removed')]
    public function organizationalProviderContributionShouldBeRemoved(string $id): void
    {
        $contribution = $this->getOrganizationalProviderContributionRepository()->find(new OrganizationalProviderContributionId($id));

        Assert::assertTrue(null === $contribution || $contribution->isRemoved());
    }

    #[Then('there should be :count active organizational provider contributions for :subjectType carbon footprint :footprintId')]
    public function thereShouldBeActiveOrganizationalProviderContributions(string $count, string $subjectType, string $footprintId): void
    {
        Assert::assertCount((int) $count, $this->getOrganizationalProviderContributionRepository()->findActiveByFootprint(CarbonFootprintReportSubjectType::from($subjectType), $footprintId));
    }

    #[Then(':subjectType carbon footprint :id should have :count activities of provider :providerId in source :source')]
    public function carbonFootprintShouldHaveActivitiesOfProviderInSource(string $subjectType, string $id, string $count, string $providerId, string $source): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $providerIdObject = new ProviderId($providerId);

        $references = array_filter(
            $carbonFootprint->activityReferences(),
            static fn($reference): bool => Source::from($source) === $reference->placement->source
                && null !== $reference->placement->providerId
                && $providerIdObject->equals($reference->placement->providerId),
        );

        Assert::assertCount((int) $count, $references);
    }

    #[Then(':subjectType carbon footprint :id should have :count activities of provider :providerId in source :source for property :propertyId')]
    public function carbonFootprintShouldHaveActivitiesOfProviderInSourceForProperty(string $subjectType, string $id, string $count, string $providerId, string $source, string $propertyId): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $providerIdObject = new ProviderId($providerId);
        $propertyIdObject = 'unassigned' === $propertyId ? null : new PropertyId($propertyId);

        $references = array_filter(
            $carbonFootprint->activityReferences(),
            static fn($reference): bool => Source::from($source) === $reference->placement->source
                && null !== $reference->placement->providerId
                && $providerIdObject->equals($reference->placement->providerId)
                && (null === $propertyIdObject ? null === $reference->placement->propertyId : null !== $reference->placement->propertyId && $propertyIdObject->equals($reference->placement->propertyId)),
        );

        Assert::assertCount((int) $count, $references);
    }

    private function registerOrganizationalProviderContribution(
        string $id,
        string $subjectType,
        string $footprintId,
        string $providerId,
        string $account,
        string $mode,
        string $token,
        string $sources,
        ?PropertyId $propertyId,
    ): void {
        $contribution = new OrganizationalProviderContribution(new OrganizationalProviderContributionId($id));
        $contribution->register(
            subjectType: CarbonFootprintReportSubjectType::from($subjectType),
            carbonFootprintId: $footprintId,
            accountId: new AccountId($account),
            providerId: new ProviderId($providerId),
            propertyId: $propertyId,
            mode: ProviderContributionMode::from($mode),
            token: $token,
            sources: array_map(static fn(string $source): Source => Source::from(trim($source)), explode(',', $sources)),
            companyName: 'Provider Company',
        );

        $this->getOrganizationalProviderContributionRepository()->add($contribution);
    }

    private function getOrganizationalProviderContributionRepository(): OrganizationalProviderContributionRepository
    {
        return $this->client->getContainer()->get(OrganizationalProviderContributionRepository::class);
    }

    private function findCarbonFootprint(string $subjectType, string $id): CarbonFootprint
    {
        $carbonFootprint = match ($subjectType) {
            'event' => $this->getEventCarbonFootprintRepository()->find(new EventCarbonFootprintId($id)),
            'property' => $this->getPropertyCarbonFootprintRepository()->find(new PropertyCarbonFootprintId($id)),
            'company' => $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id)),
            default => null,
        };

        Assert::assertNotNull($carbonFootprint);

        return $carbonFootprint;
    }

    private function getPropertyCarbonFootprintRepository(): PropertyCarbonFootprintRepository
    {
        return $this->client->getContainer()->get(PropertyCarbonFootprintRepository::class);
    }

    private function getCompanyCarbonFootprintRepository(): CompanyCarbonFootprintRepository
    {
        return $this->client->getContainer()->get(CompanyCarbonFootprintRepository::class);
    }

    #[Given('mitigation measure category :name with id :id exists')]
    public function mitigationMeasureCategoryExists(string $name, string $id): void
    {
        $this->getMitigationMeasureCategoryRepository()->add(MitigationMeasureCategoryMother::fromNameAndId($name, $id));
    }

    #[Given('mitigation measure :name with id :id of category :categoryId reducing source :source between :min and :max exists')]
    public function mitigationMeasureExists(string $name, string $id, string $categoryId, string $source, string $min, string $max): void
    {
        $measure = MitigationMeasureMother::from(name: $name, id: $id, categoryId: new MitigationMeasureCategoryId($categoryId));
        $measure->setEffect(Source::from($source), (float) $min, (float) $max);

        $this->getMitigationMeasureRepository()->add($measure);
    }

    #[Given('mitigation measure :id is translated to :locale as :name')]
    public function mitigationMeasureIsTranslated(string $id, string $locale, string $name): void
    {
        $measure = $this->getMitigationMeasureRepository()->find(new MitigationMeasureId($id));
        Assert::assertNotNull($measure);

        $localeEnum = Locale::tryFrom($locale);
        Assert::assertNotNull($localeEnum);

        $measure->translate($name, $measure->defaultDescription(), $localeEnum);

        $this->getMitigationMeasureRepository()->add($measure);
    }

    #[Given('mitigation action :name with id :id for :subjectType carbon footprint :carbonFootprintId of account :account with target year :year exists')]
    public function mitigationActionExists(string $name, string $id, string $subjectType, string $carbonFootprintId, string $account, string $year): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $carbonFootprintId);

        $this->getMitigationActionRepository()->add(MitigationActionMother::from(
            id: $id,
            carbonFootprintId: $carbonFootprintId,
            name: $name,
            baseYear: $carbonFootprint->calculationYear(),
            targetYear: (int) $year,
            subjectType: CarbonFootprintReportSubjectType::from($subjectType),
            accountId: $account,
        ));
    }

    #[Given('mitigation action :id reduces source :source item :itemId by :reduction')]
    public function mitigationActionReducesSource(string $id, string $source, string $itemId, string $reduction): void
    {
        $action = $this->getMitigationActionRepository()->find(new MitigationActionId($id));
        Assert::assertNotNull($action);

        $action->setEffect(Source::from($source), $itemId, $itemId, (float) $reduction);

        $this->getMitigationActionRepository()->add($action);
    }

    #[Given(':subjectType carbon footprint :id has a combustible :combustibleId activity of :co2eq kg')]
    #[Given(':subjectType carbon footprint :id has a combustible :combustibleId activity of :co2eq kg for property :propertyId')]
    public function carbonFootprintHasACombustibleActivity(string $subjectType, string $id, string $combustibleId, string $co2eq, ?string $propertyId = null): void
    {
        $carbonFootprint = $this->findCarbonFootprint($subjectType, $id);
        $accountId = $carbonFootprint->accountId();
        Assert::assertNotNull($accountId);

        $activity = CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId,
            combustibleId: new CombustibleId($combustibleId),
            emissionFactor: new EmissionFactor('1.5', '1.0', '0.2', '0.3', $carbonFootprint->calculationYear(), EmissionFactorGroupMother::default()->id(), Unit::Liter, 'IPCC'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(100.0, Unit::Liter),
        );
        $this->getActivityRepository()->save($activity);

        $carbonFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: null !== $propertyId ? new PropertyId($propertyId) : null));

        match ($subjectType) {
            'property' => $this->getPropertyCarbonFootprintRepository()->add($this->propertyCarbonFootprint($id)),
            default => $this->getCompanyCarbonFootprintRepository()->add($this->companyCarbonFootprint($id)),
        };
    }

    #[Given('property carbon footprint :id has its mitigation plan started')]
    public function propertyCarbonFootprintHasItsMitigationPlanStarted(string $id): void
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprint($id);
        $propertyCarbonFootprint->answerScope(Scope::Scope3);

        $this->getPropertyCarbonFootprintRepository()->add($propertyCarbonFootprint);
    }

    #[Given('company carbon footprint :id has its mitigation plan started')]
    public function companyCarbonFootprintHasItsMitigationPlanStarted(string $id): void
    {
        $companyCarbonFootprint = $this->companyCarbonFootprint($id);
        $companyCarbonFootprint->answerScope(Scope::Scope3);

        $this->getCompanyCarbonFootprintRepository()->add($companyCarbonFootprint);
    }

    #[When('I submit the providers step form')]
    public function iSubmitTheProvidersStepForm(): void
    {
        $this->client->submit($this->client->getCrawler()->selectButton('Siguiente')->form());
    }

    #[Then('the wizard should show the step :label')]
    public function theWizardShouldShowTheStep(string $label): void
    {
        Assert::assertContains($label, $this->wizardStepLabels());
    }

    #[Then('the wizard should not show the step :label')]
    public function theWizardShouldNotShowTheStep(string $label): void
    {
        Assert::assertNotContains($label, $this->wizardStepLabels());
    }

    /** @return list<string> */
    private function wizardStepLabels(): array
    {
        return $this->client->getCrawler()->filter('.steps li span.d-none')->each(static fn($node): string => trim($node->text()));
    }

    #[When('I submit the mitigation plan step form')]
    public function iSubmitTheMitigationPlanStepForm(): void
    {
        $this->client->submit($this->client->getCrawler()->selectButton('Siguiente')->form());
    }

    #[Then(':subjectType carbon footprint :id should have :count mitigation actions')]
    public function carbonFootprintShouldHaveMitigationActions(string $subjectType, string $id, int $count): void
    {
        $actions = $this->getMitigationActionRepository()->findByFilter(new MitigationActionFilter(
            subjectType: CarbonFootprintReportSubjectType::from($subjectType),
            carbonFootprintId: $id,
            limit: 0,
        ));

        Assert::assertCount($count, $actions);
    }

    #[Then('mitigation action :id should be named :name in :locale')]
    public function mitigationActionShouldBeNamed(string $id, string $name, string $locale): void
    {
        $action = $this->getMitigationActionRepository()->find(new MitigationActionId($id));
        Assert::assertNotNull($action);

        $localeEnum = Locale::tryFrom($locale);
        Assert::assertNotNull($localeEnum);

        $translation = $action->translations()->getTranslationFor($localeEnum);
        $translatedName = $translation instanceof MitigationActionTranslation ? $translation->name() : $action->defaultName();

        Assert::assertSame($name, Locale::Spanish === $localeEnum ? $action->defaultName() : $translatedName);
    }

    #[Then('the only mitigation action of :subjectType carbon footprint :id should be named :name in :locale')]
    public function theOnlyMitigationActionShouldBeNamed(string $subjectType, string $id, string $name, string $locale): void
    {
        $actions = $this->getMitigationActionRepository()->findByFilter(new MitigationActionFilter(
            subjectType: CarbonFootprintReportSubjectType::from($subjectType),
            carbonFootprintId: $id,
            limit: 0,
        ));

        Assert::assertCount(1, $actions);

        $this->mitigationActionShouldBeNamed((string) $actions[0]->id(), $name, $locale);
    }

    #[Then('the only mitigation action of :subjectType carbon footprint :id should reduce source :source item :itemId by :reduction')]
    public function theOnlyMitigationActionShouldReduce(string $subjectType, string $id, string $source, string $itemId, string $reduction): void
    {
        $actions = $this->getMitigationActionRepository()->findByFilter(new MitigationActionFilter(
            subjectType: CarbonFootprintReportSubjectType::from($subjectType),
            carbonFootprintId: $id,
            limit: 0,
        ));

        Assert::assertCount(1, $actions);

        $effect = $actions[0]->effects()->forSourceAndItem(Source::from($source), $itemId);
        Assert::assertNotNull($effect);
        Assert::assertEqualsWithDelta((float) $reduction, $effect->reduction(), 0.0001);
    }

    private function propertyCarbonFootprint(string $id): PropertyCarbonFootprint
    {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintRepository()->find(new PropertyCarbonFootprintId($id));
        Assert::assertNotNull($propertyCarbonFootprint);

        return $propertyCarbonFootprint;
    }

    private function companyCarbonFootprint(string $id): CompanyCarbonFootprint
    {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id));
        Assert::assertNotNull($companyCarbonFootprint);

        return $companyCarbonFootprint;
    }

    private function getMitigationMeasureCategoryRepository(): MitigationMeasureCategoryRepository
    {
        return $this->client->getContainer()->get(MitigationMeasureCategoryRepository::class);
    }

    private function getMitigationMeasureRepository(): MitigationMeasureRepository
    {
        return $this->client->getContainer()->get(MitigationMeasureRepository::class);
    }

    private function getMitigationActionRepository(): MitigationActionRepository
    {
        return $this->client->getContainer()->get(MitigationActionRepository::class);
    }

    private function getActivityRepository(): ActivityRepository
    {
        return $this->client->getContainer()->get(ActivityRepository::class);
    }
}
