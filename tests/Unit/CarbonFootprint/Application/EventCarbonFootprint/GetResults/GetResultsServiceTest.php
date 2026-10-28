<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetResultsServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private GetResultsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new GetResultsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create()),
        );
    }

    public function testAllPhasesAndSourcesArePresentWithCorrectBuckets(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $activity = MaterialActivityMother::registered(new AccountId('account-id'));
        $activity->update($activity->materialId(), $activity->condition(), $activity->emissionFactor(), new Emissions('12', '12', '0', '0'), $activity->quantity());
        $this->activityRepository->save($activity);
        $eventCarbonFootprint->addActivity($activity->id(), new ActivityPlacement(Phase::Materials, Source::Materials));

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $response = ($this->sut)(new GetResultsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertCount(26, $response->emissionsBySource);
        $this->assertCount(4, $response->emissionsByPhase);
        $this->assertCount(4, $response->emissionsByPhaseAndSource);

        foreach ($response->emissionsByPhaseAndSource as $sources) {
            $this->assertCount(26, $sources);
        }

        $this->assertSame('12', $response->emissionsBySource[Source::Materials->value]->co2eq());
        $this->assertSame('12', $response->emissionsByPhase[Phase::Materials->value]->co2eq());
        $this->assertSame('12', $response->emissionsByPhaseAndSource[Phase::Materials->value][Source::Materials->value]->co2eq());
        $this->assertSame('0', $response->emissionsBySource[Source::WasteManagement->value]->co2eq());
    }

    public function testExposesTheExclusions(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $eventCarbonFootprint->excludeSource(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'a comment');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $response = ($this->sut)(new GetResultsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertCount(1, $response->exclusions);
        $this->assertSame(Phase::Assembly, $response->exclusions[0]->stage());
        $this->assertSame(Source::WasteManagement, $response->exclusions[0]->source());
        $this->assertSame(ExclusionReason::NoDataAvailable, $response->exclusions[0]->reason());
    }

    public function testExposesTheDualElectricityReporting(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $material = MaterialActivityMother::registered(new AccountId('account-id'));
        $withLocationBased = ElectricityActivityMother::registeredWithLocationBasedResult(new AccountId('account-id'));
        $withoutLocationBased = ElectricityActivityMother::registered(new AccountId('account-id'));
        $this->activityRepository->save($material);
        $this->activityRepository->save($withLocationBased);
        $this->activityRepository->save($withoutLocationBased);
        $eventCarbonFootprint->addActivity($material->id(), new ActivityPlacement(Phase::Materials, Source::Materials));
        $eventCarbonFootprint->addActivity($withLocationBased->id(), new ActivityPlacement(Phase::Assembly, Source::BuildingElectricity));
        $eventCarbonFootprint->addActivity($withoutLocationBased->id(), new ActivityPlacement(Phase::Celebration, Source::BuildingElectricity));

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $response = ($this->sut)(new GetResultsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $activitiesTotal = (float) $material->emissions()->co2eq() + (float) $withLocationBased->emissions()->co2eq() + (float) $withoutLocationBased->emissions()->co2eq();
        $this->assertSame('30', $response->marketBasedElectricityEmissions->co2eq());
        $this->assertSame('180', $response->locationBasedElectricityEmissions->co2eq());
        $this->assertSame(2, $response->electricityActivities);
        $this->assertSame(1, $response->electricityActivitiesWithoutLocationBased);
        $this->assertSame($activitiesTotal - 15.0 + 180.0, (float) $response->locationBasedTotalEmissions->co2eq());
    }

    public function testEventCarbonFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new GetResultsRequest(EventCarbonFootprintMother::random()->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new GetResultsRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
