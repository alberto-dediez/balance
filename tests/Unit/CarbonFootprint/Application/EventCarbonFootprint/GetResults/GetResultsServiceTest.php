<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
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
            $this->activityRepository,
        );
    }

    public function testAllPhasesAndSourcesArePresentWithCorrectBuckets(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $activity = MaterialActivityMother::registered(new AccountId('account-id'));
        $activity->update($activity->materialId(), $activity->condition(), $activity->emissionFactor(), new Emissions('12', '12', '0', '0'), $activity->quantity());
        $this->activityRepository->save($activity);
        $eventCarbonFootprint->addActivity($activity->id(), Phase::Materials, Source::Materials);

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $response = ($this->sut)(new GetResultsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertCount(10, $response->emissionsBySource);
        $this->assertCount(4, $response->emissionsByPhase);
        $this->assertCount(4, $response->emissionsByPhaseAndSource);

        foreach ($response->emissionsByPhaseAndSource as $sources) {
            $this->assertCount(10, $sources);
        }

        $this->assertSame('12', $response->emissionsBySource[Source::Materials->value]->co2eq());
        $this->assertSame('12', $response->emissionsByPhase[Phase::Materials->value]->co2eq());
        $this->assertSame('12', $response->emissionsByPhaseAndSource[Phase::Materials->value][Source::Materials->value]->co2eq());
        $this->assertSame('0', $response->emissionsBySource[Source::WasteManagement->value]->co2eq());
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
