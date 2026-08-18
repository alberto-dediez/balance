<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class LedgerEntryRecorded extends DomainEvent
{
    protected static string $eventName = 'ledgerEntryRecorded';

    public function __construct(
        private LedgerEntryId $ledgerEntryId,
        private LedgerEntryType $type,
        private LedgerBeneficiary $beneficiary,
        private ?string $beneficiaryId,
        private float $amount,
        private string $currency,
        private ?ProjectContributionId $projectContributionId = null,
        private ?ProjectId $projectId = null,
        private ?CreditPurchaseId $creditPurchaseId = null,
        private ?float $tonnes = null,
        private ?LedgerEntryId $reversesEntryId = null,
        private ?string $concept = null,
        private ?UserId $recordedBy = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function ledgerEntryId(): LedgerEntryId
    {
        return $this->ledgerEntryId;
    }

    public function type(): LedgerEntryType
    {
        return $this->type;
    }

    public function beneficiary(): LedgerBeneficiary
    {
        return $this->beneficiary;
    }

    public function beneficiaryId(): ?string
    {
        return $this->beneficiaryId;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function projectContributionId(): ?ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function creditPurchaseId(): ?CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function tonnes(): ?float
    {
        return $this->tonnes;
    }

    public function reversesEntryId(): ?LedgerEntryId
    {
        return $this->reversesEntryId;
    }

    public function concept(): ?string
    {
        return $this->concept;
    }

    public function recordedBy(): ?UserId
    {
        return $this->recordedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->ledgerEntryId();
    }

    public function payload(): array
    {
        return [
            'ledgerEntryId' => (string) $this->ledgerEntryId,
            'type' => $this->type->value,
            'beneficiary' => $this->beneficiary->value,
            'beneficiaryId' => $this->beneficiaryId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'projectContributionId' => null !== $this->projectContributionId ? (string) $this->projectContributionId : null,
            'projectId' => null !== $this->projectId ? (string) $this->projectId : null,
            'creditPurchaseId' => null !== $this->creditPurchaseId ? (string) $this->creditPurchaseId : null,
            'tonnes' => $this->tonnes,
            'reversesEntryId' => null !== $this->reversesEntryId ? (string) $this->reversesEntryId : null,
            'concept' => $this->concept,
            'recordedBy' => null !== $this->recordedBy ? (string) $this->recordedBy : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new LedgerEntryRecorded(
            ledgerEntryId: new LedgerEntryId($payload['ledgerEntryId']),
            type: LedgerEntryType::from((string) $payload['type']),
            beneficiary: LedgerBeneficiary::from((string) $payload['beneficiary']),
            beneficiaryId: $payload['beneficiaryId'],
            amount: (float) $payload['amount'],
            currency: (string) $payload['currency'],
            projectContributionId: null !== $payload['projectContributionId'] ? new ProjectContributionId($payload['projectContributionId']) : null,
            projectId: null !== $payload['projectId'] ? new ProjectId($payload['projectId']) : null,
            creditPurchaseId: null !== $payload['creditPurchaseId'] ? new CreditPurchaseId($payload['creditPurchaseId']) : null,
            tonnes: null !== $payload['tonnes'] ? (float) $payload['tonnes'] : null,
            reversesEntryId: null !== $payload['reversesEntryId'] ? new LedgerEntryId($payload['reversesEntryId']) : null,
            concept: $payload['concept'],
            recordedBy: null !== $payload['recordedBy'] ? new UserId($payload['recordedBy']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
