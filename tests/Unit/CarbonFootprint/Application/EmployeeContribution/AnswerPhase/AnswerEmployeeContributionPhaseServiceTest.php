<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\AnswerPhase;

use App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase\AnswerEmployeeContributionPhaseRequest;
use App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase\AnswerEmployeeContributionPhaseService;
use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionStepStatus;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class AnswerEmployeeContributionPhaseServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private AnswerEmployeeContributionPhaseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new AnswerEmployeeContributionPhaseService(
            new EmployeeContributionAuthenticator($this->employeeContributionRepository),
            $this->employeeContributionRepository,
            $this->eventCarbonFootprintRepository,
        );
    }

    public function testAnsweringAssemblyStoresAnswersAndAdvancesStepStatus(): void
    {
        $contribution = $this->registeredContribution();

        ($this->sut)($this->request($contribution->token(), Phase::Assembly));

        $this->assertTrue($contribution->workedAssembly());
        $this->assertSame(120.0, $contribution->assemblyDistanceKm());
        $this->assertSame(2, $contribution->assemblyVehicleOccupants());
        $this->assertSame(3, $contribution->assemblyDays());
        $this->assertSame(2, $contribution->assemblyHotelNights());
        $this->assertSame(EmployeeContributionStepStatus::AssemblyAnswered, $contribution->stepStatus());
        $this->assertTrue($contribution->status()->isPending());
    }

    public function testAnsweringBreakdownMarksTheContributionAsAnswered(): void
    {
        $contribution = $this->registeredContribution();

        ($this->sut)($this->request($contribution->token(), Phase::Assembly));
        ($this->sut)($this->request($contribution->token(), Phase::Celebration));
        ($this->sut)($this->request($contribution->token(), Phase::Breakdown));

        $this->assertSame(EmployeeContributionStepStatus::Completed, $contribution->stepStatus());
        $this->assertTrue($contribution->status()->isAnswered());
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(EmployeeContributionTokenInvalidException::class);

        ($this->sut)($this->request('invalid-token', Phase::Assembly));
    }

    public function testIgnoredContributionThrows(): void
    {
        $contribution = $this->registeredContribution();
        $contribution->ignore();
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(EmployeeContributionIgnoredException::class);

        ($this->sut)($this->request($contribution->token(), Phase::Assembly));
    }

    public function testCompletedEventCarbonFootprintThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $eventCarbonFootprint->complete(new Emissions(), new Emissions());
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(EmployeeContributionClosedException::class);

        ($this->sut)($this->request($contribution->token(), Phase::Assembly));
    }

    private function request(string $token, Phase $phase): AnswerEmployeeContributionPhaseRequest
    {
        return new AnswerEmployeeContributionPhaseRequest(
            token: $token,
            phase: $phase,
            worked: true,
            mobilityId: MobilityId::generate(),
            distanceKm: 120.0,
            vehicleOccupants: 2,
            days: 3,
            hotelNights: 2,
        );
    }

    private function registeredContribution(): EmployeeContribution
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($contribution);

        return $contribution;
    }
}
