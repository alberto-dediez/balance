<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

/**
 * The gateway confirmed a payment for a contribution that was already resolved as expired or
 * failed: the money came in but the credit reservation is gone, so it needs manual reconciliation.
 */
final class ProjectContributionPaymentUnreconciled extends DomainEvent
{
    protected static string $eventName = 'projectContributionPaymentUnreconciled';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        private ProjectContributionStatus $status,
        private ?PaymentId $paymentId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function status(): ProjectContributionStatus
    {
        return $this->status;
    }

    public function paymentId(): ?PaymentId
    {
        return $this->paymentId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
            'status' => $this->status->value,
            'paymentId' => null !== $this->paymentId ? (string) $this->paymentId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionPaymentUnreconciled(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            status: ProjectContributionStatus::from($payload['status']),
            paymentId: null !== $payload['paymentId'] ? new PaymentId($payload['paymentId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
