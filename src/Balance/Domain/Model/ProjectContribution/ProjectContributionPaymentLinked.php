<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectContributionPaymentLinked extends DomainEvent
{
    protected static string $eventName = 'projectContributionPaymentLinked';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        private PaymentId $paymentId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function paymentId(): PaymentId
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
            'paymentId' => (string) $this->paymentId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionPaymentLinked(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            paymentId: new PaymentId($payload['paymentId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
