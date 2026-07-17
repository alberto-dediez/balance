<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionStepStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class EmployeeContributionTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterSnapshotsEmployeeNameAndStartsPending(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();

        $this->assertSame('Employee Name', $contribution->employeeName());
        $this->assertTrue($contribution->status()->isPending());
        $this->assertSame(EmployeeContributionStepStatus::Started, $contribution->stepStatus());
        $this->assertNotEmpty($contribution->token());
    }

    public function testAnswerPhaseStoresAnswersAndAdvancesStepStatus(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();
        $mobilityId = MobilityId::generate();

        $contribution->answerPhase(Phase::Assembly, true, $mobilityId, 100.0, 2, 3, 1);

        $this->assertTrue($contribution->workedAssembly());
        $assemblyMobilityId = $contribution->assemblyMobilityId();
        $this->assertNotNull($assemblyMobilityId);
        $this->assertTrue($mobilityId->equals($assemblyMobilityId));
        $this->assertSame(100.0, $contribution->assemblyDistanceKm());
        $this->assertSame(2, $contribution->assemblyVehicleOccupants());
        $this->assertSame(3, $contribution->assemblyDays());
        $this->assertSame(1, $contribution->assemblyHotelNights());
        $this->assertSame(EmployeeContributionStepStatus::AssemblyAnswered, $contribution->stepStatus());
        $this->assertTrue($contribution->status()->isPending());
    }

    public function testAnswerPhaseDoesNotRegressStepStatus(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();

        $contribution->answerPhase(Phase::Celebration, true, null, null, null, null, null);
        $this->assertSame(EmployeeContributionStepStatus::CelebrationAnswered, $contribution->stepStatus());

        $contribution->answerPhase(Phase::Assembly, false, null, null, null, null, null);
        $this->assertSame(EmployeeContributionStepStatus::CelebrationAnswered, $contribution->stepStatus());
    }

    public function testAnsweringBreakdownCompletesAndMarksAnswered(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();

        $contribution->answerPhase(Phase::Breakdown, true, null, null, null, null, null);

        $this->assertSame(EmployeeContributionStepStatus::Completed, $contribution->stepStatus());
        $this->assertTrue($contribution->status()->isAnswered());
        $this->assertNotNull($contribution->answeredAt());
    }

    public function testAnswerPhaseRejectsMaterials(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();

        $this->expectException(\InvalidArgumentException::class);

        $contribution->answerPhase(Phase::Materials, true, null, null, null, null, null);
    }

    public function testIgnoreAndUnignore(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();

        $contribution->ignore();
        $this->assertTrue($contribution->status()->isIgnored());
        $this->assertNotNull($contribution->ignoredAt());

        $contribution->unignore();
        $this->assertTrue($contribution->status()->isPending());
        $this->assertNull($contribution->ignoredAt());
    }

    public function testUnignoreAfterAnsweredRestoresAnsweredStatus(): void
    {
        $contribution = EmployeeContributionMother::registeredEmployeeContribution();
        $contribution->answer();

        $contribution->ignore();
        $contribution->unignore();

        $this->assertTrue($contribution->status()->isAnswered());
    }
}
