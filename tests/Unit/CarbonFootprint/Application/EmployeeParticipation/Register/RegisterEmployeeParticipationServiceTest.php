<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeParticipation\Register;

use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterEmployeeParticipationServiceTest extends TestCase
{
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private RegisterEmployeeParticipationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();

        $this->sut = new RegisterEmployeeParticipationService(
            $this->employeeParticipationRepository,
            $this->eventCarbonFootprintRepository,
        );
    }

    public function testRegisteringCreatesAnOpenParticipation(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $response = ($this->sut)(new RegisterEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertFalse($response->employeeParticipation->status()->isClosed());
        $this->assertTrue($response->employeeParticipation->eventCarbonFootprintId()?->equals($eventCarbonFootprint->id()));

        $stored = $this->employeeParticipationRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertNotNull($stored);
        $this->assertTrue($stored->id()->equals($response->employeeParticipation->id()));
    }

    public function testRegisteringTwiceReturnsTheExistingParticipation(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $first = ($this->sut)(new RegisterEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());
        $second = ($this->sut)(new RegisterEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertTrue($first->employeeParticipation->id()->equals($second->employeeParticipation->id()));
    }

    public function testMissingEventCarbonFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RegisterEmployeeParticipationRequest(EventCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
