<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CateringItemCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MachineryFuelCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MaterialCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MerchandisingItemCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MobilityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\OvernightStayCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\RoadTransportCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\WasteCatalogProvider;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetPhaseServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryMaterialRepository $materialRepository;
    private GetPhaseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->materialRepository = MaterialRepositoryMother::inMemory();

        $catalogProviders = new ActivityCatalogProviderRegistry([
            new MaterialCatalogProvider($this->materialRepository),
            new CateringItemCatalogProvider(CateringItemRepositoryMother::inMemory()),
            new MerchandisingItemCatalogProvider(MerchandisingItemRepositoryMother::inMemory()),
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new RoadTransportCatalogProvider(RoadTransportRepositoryMother::inMemory()),
            new MachineryFuelCatalogProvider(MachineryFuelRepositoryMother::inMemory()),
            new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
            new OvernightStayCatalogProvider(OvernightStayRepositoryMother::inMemory()),
            new MobilityCatalogProvider(MobilityRepositoryMother::inMemory()),
            new WasteCatalogProvider(WasteRepositoryMother::inMemory()),
        ]);

        $this->sut = new GetPhaseService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $catalogProviders,
            $this->activityRepository,
        );
    }

    public function testReturnsOneSectionPerPhaseSectionDefinition(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $response = ($this->sut)(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Materials), $this->ownerUser());

        $this->assertCount(count(Phase::Materials->sections()), $response->sections);
        foreach (Phase::Materials->sections() as $index => $definition) {
            $this->assertSame($definition->key, $response->sections[$index]->definition->key);
        }
    }

    public function testActivitiesSharingASourceAppearOnlyInTheirOwnSection(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $machineryFuelActivity = MachineryFuelActivityMother::registered();
        $electricityActivity = ElectricityActivityMother::registered();
        $this->activityRepository->save($machineryFuelActivity);
        $this->activityRepository->save($electricityActivity);
        $eventCarbonFootprint->addActivity($machineryFuelActivity->id(), Phase::Assembly, Source::MachineryOperation);
        $eventCarbonFootprint->addActivity($electricityActivity->id(), Phase::Assembly, Source::MachineryOperation);

        $response = ($this->sut)(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Assembly), $this->ownerUser());

        $machineryFuels = $this->section($response, 'machineryFuels');
        $this->assertCount(1, $machineryFuels->activities);
        $this->assertSame($machineryFuelActivity, $machineryFuels->activities[0]);

        $machineryElectricity = $this->section($response, 'machineryElectricity');
        $this->assertCount(1, $machineryElectricity->activities);
        $this->assertSame($electricityActivity, $machineryElectricity->activities[0]);
    }

    public function testCatalogsContainOnlyItemsHavingEmissionFactorForTheCalculationYearAndGroup(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $withFactor = $this->materialWithEmissionFactorFor($eventCarbonFootprint);
        $withoutFactor = MaterialMother::registeredMaterial();
        $this->materialRepository->add($withFactor);
        $this->materialRepository->add($withoutFactor);

        $response = ($this->sut)(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Materials), $this->ownerUser());

        $this->assertSame([$withFactor], $this->section($response, 'materials')->catalog);
    }

    public function testCatalogsAreEmptyWhenNoEmissionFactorGroupIsConfigured(): void
    {
        $eventCarbonFootprint = new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            accountId: new AccountId('account-id'),
        );
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->materialRepository->add($this->materialWithEmissionFactorFor($eventCarbonFootprint));

        $response = ($this->sut)(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Materials), $this->ownerUser());

        foreach ($response->sections as $section) {
            $this->assertSame([], $section->catalog);
        }
    }

    public function testExposesTheExclusionsOfThePhaseIndexedBySource(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventCarbonFootprint->excludeSource(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'a comment');
        $eventCarbonFootprint->excludeSource(Phase::Celebration, Source::FixedFacilities, ExclusionReason::NotApplicable);

        $response = ($this->sut)(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Assembly), $this->ownerUser());

        $this->assertCount(1, $response->exclusions);
        $this->assertArrayHasKey(Source::WasteManagement->value, $response->exclusions);
        $this->assertSame(ExclusionReason::NoDataAvailable, $response->exclusions[Source::WasteManagement->value]->reason());
        $this->assertSame('a comment', $response->exclusions[Source::WasteManagement->value]->comment());
    }

    public function testEventCarbonFootprintNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new GetPhaseRequest(EventCarbonFootprintMother::random()->id(), Phase::Materials), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Materials),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function materialWithEmissionFactorFor(EventCarbonFootprint $eventCarbonFootprint): Material
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $material = MaterialMother::registeredMaterial();
        $material->setEmissionFactor(
            ActivityMother::emissionFactor($groupId)->withYear($eventCarbonFootprint->calculationYear()),
            ActivityMother::emissionFactor($groupId)->withYear($eventCarbonFootprint->calculationYear()),
        );

        return $material;
    }

    private function section(GetPhaseResponse $response, string $key): PhaseSection
    {
        foreach ($response->sections as $section) {
            if ($section->definition->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found.', $key));
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
