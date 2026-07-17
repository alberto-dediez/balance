<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\GetByToken;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthenticator;
use App\CarbonFootprint\Application\EmployeeContribution\GetByToken\GetEmployeeContributionByTokenRequest;
use App\CarbonFootprint\Application\EmployeeContribution\GetByToken\GetEmployeeContributionByTokenService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use PHPUnit\Framework\TestCase;

class GetEmployeeContributionByTokenServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEventRepository $eventRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private GetEmployeeContributionByTokenService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new GetEmployeeContributionByTokenService(
            new EmployeeContributionAuthenticator($this->employeeContributionRepository),
            $this->eventCarbonFootprintRepository,
            $this->eventRepository,
            $this->mobilityRepository,
        );
    }

    public function testValidTokenReturnsContributionEventCarbonFootprintEventAndMobilities(): void
    {
        $event = EventMother::registeredEvent();
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) $event->id(), 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $mobility = MobilityMother::registeredMobility();
        $this->mobilityRepository->add($mobility);

        $response = ($this->sut)(new GetEmployeeContributionByTokenRequest($contribution->token()));

        $this->assertTrue($response->employeeContribution->id()->equals($contribution->id()));
        $this->assertTrue($response->eventCarbonFootprint->id()->equals($eventCarbonFootprint->id()));
        $this->assertTrue($response->event->id()->equals($event->id()));
        $this->assertCount(1, $response->mobilities);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(EmployeeContributionTokenInvalidException::class);

        ($this->sut)(new GetEmployeeContributionByTokenRequest('invalid-token'));
    }

    public function testMissingEventCarbonFootprintThrows(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(EmployeeContributionTokenInvalidException::class);

        ($this->sut)(new GetEmployeeContributionByTokenRequest($contribution->token()));
    }

    public function testMissingEventThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(EmployeeContributionTokenInvalidException::class);

        ($this->sut)(new GetEmployeeContributionByTokenRequest($contribution->token()));
    }
}
