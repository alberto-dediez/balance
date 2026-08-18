<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\DomainEvent;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayoutCreated extends DomainEvent
{
    protected static string $eventName = 'payoutCreated';

    public function __construct(
        private PayoutId $payoutId,
        private string $number,
        private PayeeId $payeeId,
        private float $baseAmount,
        private float $payableAmount,
        private string $currency,
        private int $linesCount,
        private UserId $createdBy,
        private ?\DateTimeImmutable $periodFrom = null,
        private ?\DateTimeImmutable $periodTo = null,
        private ?string $notes = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function number(): string
    {
        return $this->number;
    }

    public function payeeId(): PayeeId
    {
        return $this->payeeId;
    }

    public function baseAmount(): float
    {
        return $this->baseAmount;
    }

    public function payableAmount(): float
    {
        return $this->payableAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function linesCount(): int
    {
        return $this->linesCount;
    }

    public function createdBy(): UserId
    {
        return $this->createdBy;
    }

    public function periodFrom(): ?\DateTimeImmutable
    {
        return $this->periodFrom;
    }

    public function periodTo(): ?\DateTimeImmutable
    {
        return $this->periodTo;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payoutId();
    }

    public function payload(): array
    {
        return [
            'payoutId' => (string) $this->payoutId,
            'number' => $this->number,
            'payeeId' => (string) $this->payeeId,
            'baseAmount' => $this->baseAmount,
            'payableAmount' => $this->payableAmount,
            'currency' => $this->currency,
            'linesCount' => $this->linesCount,
            'createdBy' => (string) $this->createdBy,
            'periodFrom' => $this->periodFrom?->format(\DateTimeInterface::ATOM),
            'periodTo' => $this->periodTo?->format(\DateTimeInterface::ATOM),
            'notes' => $this->notes,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayoutCreated(
            payoutId: new PayoutId($payload['payoutId']),
            number: (string) $payload['number'],
            payeeId: new PayeeId($payload['payeeId']),
            baseAmount: (float) $payload['baseAmount'],
            payableAmount: (float) $payload['payableAmount'],
            currency: (string) $payload['currency'],
            linesCount: (int) $payload['linesCount'],
            createdBy: new UserId($payload['createdBy']),
            periodFrom: null !== $payload['periodFrom'] ? new \DateTimeImmutable((string) $payload['periodFrom']) : null,
            periodTo: null !== $payload['periodTo'] ? new \DateTimeImmutable((string) $payload['periodTo']) : null,
            notes: $payload['notes'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
