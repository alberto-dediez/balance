<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReopenEventCarbonFootprintServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private ReopenEventCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ReopenEventCarbonFootprintService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
        );
    }

    public function testReopensACompletedEventCarbonFootprint(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new ReopenEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame(EventCarbonFootprintStatus::Started, $eventCarbonFootprint->status());
        $this->assertSame('0', $eventCarbonFootprint->totalEmissions()->co2eq());
        $this->assertCount(1, $eventCarbonFootprint->activities()->activityIds());
    }

    public function testEventCarbonFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new ReopenEventCarbonFootprintRequest(EventCarbonFootprintMother::random()->id()), $this->ownerUser());
    }

    public function testNotCompletedEventCarbonFootprintThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new ReopenEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $eventCarbonFootprint = $this->completedEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new ReopenEventCarbonFootprintRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function completedEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $eventCarbonFootprint->addActivity(ActivityId::generate(), Phase::Materials, Source::Materials);
        $eventCarbonFootprint->complete(
            totalEmissions: new Emissions('30', '30', '0', '0'),
            emissionsPerAttendee: new Emissions('10', '10', '0', '0'),
        );

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
