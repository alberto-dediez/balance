<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees\AssignOrganizationalEmployeesService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

class AssignOrganizationalEmployeesServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private AssignOrganizationalEmployeesService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AssignOrganizationalEmployeesService(
            $this->locator,
            $this->employeeRepository,
            $this->contributionRepository,
            $this->participationRepository,
            $this->registerService(),
            $this->syncService(),
        );
    }

    public function testRegistersTheNewEmployeesAndAnswersTheStep(): void
    {
        $spy = new SpyDomainEventSubscriber();
        DomainEventPublisher::instance()->subscribe($spy);
        $footprint = $this->propertyFootprint();
        $ana = $this->employee(name: 'Ana');
        $luis = $this->employee(name: 'Luis');

        $this->assign((string) $footprint->id(), [$ana->id(), $luis->id()], 40);

        $contributions = $this->contributionRepository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $this->assertCount(2, $contributions);
        $this->assertCount(2, array_filter($spy->events(), static fn($event) => $event instanceof OrganizationalEmployeeContributionRegistered));

        $participation = $this->participationRepository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $this->assertNotNull($participation);
        $this->assertSame(40, $participation->totalEmployees());
        $this->assertSame(ConfidenceLevel::NinetyFive, $participation->confidenceLevel());
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testUnselectedEmployeesAreIgnoredAndReselectedOnesAreReactivated(): void
    {
        $footprint = $this->propertyFootprint();
        $ana = $this->employee(name: 'Ana');
        $luis = $this->employee(name: 'Luis');
        $this->assign((string) $footprint->id(), [$ana->id(), $luis->id()], 40);

        $this->assign((string) $footprint->id(), [$ana->id()], 40);

        $luisContribution = $this->contributionRepository->findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $luis->id());
        $this->assertNotNull($luisContribution);
        $this->assertTrue($luisContribution->status()->isIgnored());

        $this->assign((string) $footprint->id(), [$ana->id(), $luis->id()], 40);

        $this->assertTrue($luisContribution->status()->isPending());
        $this->assertCount(2, $this->contributionRepository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()));
    }

    public function testATotalBelowTheSelectedEmployeesThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $ana = $this->employee(name: 'Ana');
        $luis = $this->employee(name: 'Luis');

        $this->expectException(\InvalidArgumentException::class);

        $this->assign((string) $footprint->id(), [$ana->id(), $luis->id()], 1);
    }

    public function testAZeroTotalThrows(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(\InvalidArgumentException::class);

        $this->assign((string) $footprint->id(), [], 0);
    }

    public function testAnEmployeeOfAnotherAccountThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $stranger = $this->employee(accountId: 'other-account');

        $this->expectException(\InvalidArgumentException::class);

        $this->assign((string) $footprint->id(), [$stranger->id()], 10);
    }

    public function testACompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        $this->assign((string) $footprint->id(), [], 10);
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new AssignOrganizationalEmployeesRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), [], 10, ConfidenceLevel::NinetyFive),
            $this->user(),
        );
    }

    /** @param list<EmployeeId> $employeeIds */
    private function assign(string $footprintId, array $employeeIds, int $total): void
    {
        ($this->sut)(
            new AssignOrganizationalEmployeesRequest(CarbonFootprintReportSubjectType::Property, $footprintId, $employeeIds, $total, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );
    }
}
