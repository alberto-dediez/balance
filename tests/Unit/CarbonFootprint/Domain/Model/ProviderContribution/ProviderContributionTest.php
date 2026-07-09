<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionAnswered;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionAssemblyAnswered;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionGeneralInfoUpdated;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnored;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStepStatus;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionUnignored;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class ProviderContributionTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    private function registeredProviderContribution(): ProviderContribution
    {
        $providerContribution = new ProviderContribution(ProviderContributionId::generate());
        $providerContribution->register(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
            accountId: AccountId::generate(),
            providerId: ProviderId::generate(),
            token: ProviderContribution::generateToken(),
            companyName: 'Acme Catering',
        );

        return $providerContribution;
    }

    public function testRegisterSnapshotsCompanyNameAndStartsPending(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $this->assertSame('Acme Catering', $providerContribution->companyName());
        $this->assertSame(ProviderContributionStatus::Pending, $providerContribution->status());
        $this->assertSame(ProviderContributionStepStatus::Started, $providerContribution->stepStatus());
        $this->assertNull($providerContribution->answeredAt());
    }

    public function testAnswerTransitionsToAnsweredAndSetsAnsweredAt(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->answer();

        $this->assertSame(ProviderContributionStatus::Answered, $providerContribution->status());
        $this->assertNotNull($providerContribution->answeredAt());
        $this->assertEventPublished($providerContribution, ProviderContributionAnswered::class);
    }

    public function testAnswerIsIdempotent(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->answer();
        $firstAnsweredAt = $providerContribution->answeredAt();
        $providerContribution->clearEvents();

        $providerContribution->answer();

        $this->assertSame($firstAnsweredAt, $providerContribution->answeredAt());
        $this->assertEmpty(array_filter($providerContribution->recordedEvents(), static fn($e) => $e instanceof ProviderContributionAnswered));
    }

    public function testIgnoreAndUnignore(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->ignore();
        $this->assertSame(ProviderContributionStatus::Ignored, $providerContribution->status());
        $this->assertEventPublished($providerContribution, ProviderContributionIgnored::class);

        $providerContribution->unignore();
        $this->assertSame(ProviderContributionStatus::Pending, $providerContribution->status());
        $this->assertEventPublished($providerContribution, ProviderContributionUnignored::class);
    }

    public function testUnignoreAfterAnsweredRestoresAnsweredStatus(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->answer();
        $providerContribution->ignore();
        $providerContribution->unignore();

        $this->assertSame(ProviderContributionStatus::Answered, $providerContribution->status());
    }

    public function testUpdateGeneralInfoIsFreelyReeditable(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->updateGeneralInfo(
            companyName: 'New name',
            companyType: 'Catering',
            service: 'Comida',
            workersAssembly: 2,
            workersCelebration: 5,
            workersBreakdown: 1,
        );

        $this->assertSame('New name', $providerContribution->companyName());
        $this->assertSame('Catering', $providerContribution->companyType());
        $this->assertSame('Comida', $providerContribution->service());
        $this->assertSame(2, $providerContribution->workersAssembly());
        $this->assertSame(5, $providerContribution->workersCelebration());
        $this->assertSame(1, $providerContribution->workersBreakdown());
        $this->assertEventPublished($providerContribution, ProviderContributionGeneralInfoUpdated::class);

        $providerContribution->updateGeneralInfo(
            companyName: 'Second edit',
            companyType: null,
            service: null,
            workersAssembly: null,
            workersCelebration: null,
            workersBreakdown: null,
        );

        $this->assertSame('Second edit', $providerContribution->companyName());
        $this->assertNull($providerContribution->companyType());
    }

    public function testUpdateGeneralInfoAdvancesStepStatus(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->updateGeneralInfo(
            companyName: 'New name',
            companyType: null,
            service: null,
            workersAssembly: null,
            workersCelebration: null,
            workersBreakdown: null,
        );

        $this->assertSame(ProviderContributionStepStatus::GeneralAnswered, $providerContribution->stepStatus());
    }

    public function testAnswerPhaseAdvancesStepStatusWithoutRegressing(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->answerPhase(Phase::Assembly);
        $this->assertSame(ProviderContributionStepStatus::AssemblyAnswered, $providerContribution->stepStatus());
        $this->assertEventPublished($providerContribution, ProviderContributionAssemblyAnswered::class);

        $providerContribution->answerPhase(Phase::Celebration);
        $this->assertSame(ProviderContributionStepStatus::CelebrationAnswered, $providerContribution->stepStatus());

        $providerContribution->answerPhase(Phase::Assembly);
        $this->assertSame(ProviderContributionStepStatus::CelebrationAnswered, $providerContribution->stepStatus());

        $providerContribution->answerPhase(Phase::Breakdown);
        $this->assertSame(ProviderContributionStepStatus::Completed, $providerContribution->stepStatus());
    }

    public function testAnswerPhaseRejectsMaterials(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $this->expectException(\InvalidArgumentException::class);
        $providerContribution->answerPhase(Phase::Materials);
    }

    public function testAnswerCompletesStepStatus(): void
    {
        $providerContribution = $this->registeredProviderContribution();

        $providerContribution->answer();

        $this->assertSame(ProviderContributionStepStatus::Completed, $providerContribution->stepStatus());
    }

    private function assertEventPublished(mixed $aggregate, string $eventClass): void
    {
        $events = array_filter($aggregate->recordedEvents(), static fn($e) => $e instanceof $eventClass);
        $this->assertNotEmpty($events);
    }
}
