<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;

class ProviderContribution implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ProviderContributionId $id,
        private ?EventCarbonFootprintId $eventCarbonFootprintId = null,
        private ?AccountId $accountId = null,
        private ?ProviderId $providerId = null,
        #[\SensitiveParameter]
        private string $token = '',
        private ProviderContributionStatus $status = ProviderContributionStatus::Pending,
        private ProviderContributionStepStatus $stepStatus = ProviderContributionStepStatus::Started,
        private ?\DateTimeImmutable $answeredAt = null,
        private ?\DateTimeImmutable $ignoredAt = null,
        private ?string $companyName = null,
        private ?string $companyType = null,
        private ?string $service = null,
        private ?int $workersAssembly = null,
        private ?int $workersCelebration = null,
        private ?int $workersBreakdown = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?ConsentProof $consentProof = null,
    ) {
        $this->events = $events;
    }

    public function id(): ProviderContributionId
    {
        return $this->id;
    }

    public function eventCarbonFootprintId(): ?EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function status(): ProviderContributionStatus
    {
        return $this->status;
    }

    public function stepStatus(): ProviderContributionStepStatus
    {
        return $this->stepStatus;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function ignoredAt(): ?\DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function companyType(): ?string
    {
        return $this->companyType;
    }

    public function service(): ?string
    {
        return $this->service;
    }

    public function workersAssembly(): ?int
    {
        return $this->workersAssembly;
    }

    public function workersCelebration(): ?int
    {
        return $this->workersCelebration;
    }

    public function workersBreakdown(): ?int
    {
        return $this->workersBreakdown;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        EventCarbonFootprintId $eventCarbonFootprintId,
        AccountId $accountId,
        ProviderId $providerId,
        #[\SensitiveParameter]
        string $token,
        ?string $companyName,
    ): void {
        $this->recordApplyAndPublish(
            new ProviderContributionRegistered(
                providerContributionId: $this->id(),
                eventCarbonFootprintId: $eventCarbonFootprintId,
                accountId: $accountId,
                providerId: $providerId,
                token: $token,
                companyName: $companyName,
            ),
        );
    }

    public function answer(): void
    {
        if ($this->status->isAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProviderContributionAnswered(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function ignore(): void
    {
        if ($this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProviderContributionIgnored(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function unignore(): void
    {
        if (!$this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProviderContributionUnignored(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function answerPhase(Phase $phase): void
    {
        match ($phase) {
            Phase::Assembly => $this->answerAssembly(),
            Phase::Celebration => $this->answerCelebration(),
            Phase::Breakdown => $this->answerBreakdown(),
            Phase::Materials => throw new \InvalidArgumentException('La fase de materiales no está disponible para proveedores'),
        };
    }

    public function answerAssembly(): void
    {
        $this->recordApplyAndPublish(
            new ProviderContributionAssemblyAnswered(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function answerCelebration(): void
    {
        $this->recordApplyAndPublish(
            new ProviderContributionCelebrationAnswered(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function answerBreakdown(): void
    {
        $this->recordApplyAndPublish(
            new ProviderContributionBreakdownAnswered(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function resendNotification(): void
    {
        $this->recordApplyAndPublish(
            new ProviderContributionNotificationResent(
                providerContributionId: $this->id(),
            ),
        );
    }

    public function updateGeneralInfo(
        ?string $companyName,
        ?string $companyType,
        ?string $service,
        ?int $workersAssembly,
        ?int $workersCelebration,
        ?int $workersBreakdown,
    ): void {
        $this->recordApplyAndPublish(
            new ProviderContributionGeneralInfoUpdated(
                providerContributionId: $this->id(),
                companyName: $companyName,
                companyType: $companyType,
                service: $service,
                workersAssembly: $workersAssembly,
                workersCelebration: $workersCelebration,
                workersBreakdown: $workersBreakdown,
            ),
        );
    }

    public function applyProviderContributionRegistered(ProviderContributionRegistered $event): void
    {
        $this->eventCarbonFootprintId = $event->eventCarbonFootprintId();
        $this->accountId = $event->accountId();
        $this->providerId = $event->providerId();
        $this->token = $event->token();
        $this->companyName = $event->companyName();
        $this->status = ProviderContributionStatus::Pending;
        $this->stepStatus = ProviderContributionStepStatus::Started;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyProviderContributionAnswered(ProviderContributionAnswered $event): void
    {
        $this->status = ProviderContributionStatus::Answered;
        $this->advanceStepStatus(ProviderContributionStepStatus::Completed);
        $this->answeredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionAssemblyAnswered(ProviderContributionAssemblyAnswered $event): void
    {
        $this->advanceStepStatus(ProviderContributionStepStatus::AssemblyAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionCelebrationAnswered(ProviderContributionCelebrationAnswered $event): void
    {
        $this->advanceStepStatus(ProviderContributionStepStatus::CelebrationAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionBreakdownAnswered(ProviderContributionBreakdownAnswered $event): void
    {
        $this->advanceStepStatus(ProviderContributionStepStatus::Completed);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionIgnored(ProviderContributionIgnored $event): void
    {
        $this->status = ProviderContributionStatus::Ignored;
        $this->ignoredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionUnignored(ProviderContributionUnignored $event): void
    {
        $this->status = $this->answeredAt instanceof \DateTimeImmutable ? ProviderContributionStatus::Answered : ProviderContributionStatus::Pending;
        $this->ignoredAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionNotificationResent(ProviderContributionNotificationResent $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProviderContributionGeneralInfoUpdated(ProviderContributionGeneralInfoUpdated $event): void
    {
        $this->companyName = $event->companyName();
        $this->companyType = $event->companyType();
        $this->service = $event->service();
        $this->workersAssembly = $event->workersAssembly();
        $this->workersCelebration = $event->workersCelebration();
        $this->workersBreakdown = $event->workersBreakdown();
        $this->advanceStepStatus(ProviderContributionStepStatus::GeneralAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function hasConsent(): bool
    {
        return null !== $this->consentProof?->consentedAt();
    }

    public function grantConsent(ConsentProof $consentProof): void
    {
        $this->recordApplyAndPublish(new ProviderContributionConsentGranted($this->id(), $consentProof));
    }

    public function applyProviderContributionConsentGranted(ProviderContributionConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private function advanceStepStatus(ProviderContributionStepStatus $stepStatus): void
    {
        if ($this->stepStatus->isBefore($stepStatus)) {
            $this->stepStatus = $stepStatus;
        }
    }
}
