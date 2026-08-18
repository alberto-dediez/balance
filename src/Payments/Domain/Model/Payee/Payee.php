<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRoot;

class Payee implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private PayeeId $id,
        private ?PayeeType $type = null,
        private ?string $subjectId = null,
        private string $legalName = '',
        private ?string $taxId = null,
        private Address $address = new Address(),
        private ?string $iban = null,
        private ?string $bic = null,
        private ?string $holderName = null,
        private PayeeStatus $status = PayeeStatus::Incomplete,
        private ?string $externalId = null,
        private ?string $notes = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PayeeId
    {
        return $this->id;
    }

    public function type(): ?PayeeType
    {
        return $this->type;
    }

    public function subjectId(): ?string
    {
        return $this->subjectId;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function taxId(): ?string
    {
        return $this->taxId;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function iban(): ?string
    {
        return $this->iban;
    }

    public function maskedIban(): ?string
    {
        if (null === $this->iban || '' === $this->iban) {
            return null;
        }

        $compact = str_replace(' ', '', $this->iban);

        if (strlen($compact) <= 6) {
            return $compact;
        }

        return substr($compact, 0, 2) . str_repeat('*', strlen($compact) - 6) . substr($compact, -4);
    }

    public function bic(): ?string
    {
        return $this->bic;
    }

    public function holderName(): ?string
    {
        return $this->holderName;
    }

    public function status(): PayeeStatus
    {
        return $this->status;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isReady(): bool
    {
        return PayeeStatus::Ready === $this->status;
    }

    public function isBlocked(): bool
    {
        return PayeeStatus::Blocked === $this->status;
    }

    public function register(
        PayeeType $type,
        string $subjectId,
        string $legalName,
        ?string $taxId,
        Address $address,
        ?string $iban,
        ?string $bic,
        ?string $holderName,
        ?string $notes,
    ): void {
        if ('' === trim($subjectId)) {
            throw new \InvalidArgumentException('A payee requires a subject.');
        }

        if ('' === trim($legalName)) {
            throw new \InvalidArgumentException('A payee requires a legal name.');
        }

        $this->recordApplyAndPublish(
            new PayeeRegistered(
                payeeId: $this->id(),
                type: $type,
                subjectId: $subjectId,
                legalName: $legalName,
                taxId: $taxId,
                address: $address,
                iban: $iban,
                bic: $bic,
                holderName: $holderName,
                notes: $notes,
                status: self::statusFor($legalName, $taxId, $iban, $holderName),
            ),
        );
    }

    public function update(
        string $legalName,
        ?string $taxId,
        Address $address,
        ?string $iban,
        ?string $bic,
        ?string $holderName,
        ?string $notes,
    ): void {
        if ('' === trim($legalName)) {
            throw new \InvalidArgumentException('A payee requires a legal name.');
        }

        $this->recordApplyAndPublish(
            new PayeeUpdated(
                payeeId: $this->id(),
                legalName: $legalName,
                taxId: $taxId,
                address: $address,
                iban: $iban,
                bic: $bic,
                holderName: $holderName,
                notes: $notes,
                status: $this->isBlocked()
                    ? PayeeStatus::Blocked
                    : self::statusFor($legalName, $taxId, $iban, $holderName),
            ),
        );
    }

    public function block(): void
    {
        if ($this->isBlocked()) {
            return;
        }

        $this->recordApplyAndPublish(new PayeeBlocked(payeeId: $this->id()));
    }

    public function unblock(): void
    {
        if (!$this->isBlocked()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PayeeUnblocked(
                payeeId: $this->id(),
                status: self::statusFor($this->legalName, $this->taxId, $this->iban, $this->holderName),
            ),
        );
    }

    public function applyPayeeRegistered(PayeeRegistered $event): void
    {
        $this->type = $event->type();
        $this->subjectId = $event->subjectId();
        $this->legalName = $event->legalName();
        $this->taxId = $event->taxId();
        $this->address = $event->address();
        $this->iban = $event->iban();
        $this->bic = $event->bic();
        $this->holderName = $event->holderName();
        $this->notes = $event->notes();
        $this->status = $event->status();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPayeeUpdated(PayeeUpdated $event): void
    {
        $this->legalName = $event->legalName();
        $this->taxId = $event->taxId();
        $this->address = $event->address();
        $this->iban = $event->iban();
        $this->bic = $event->bic();
        $this->holderName = $event->holderName();
        $this->notes = $event->notes();
        $this->status = $event->status();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPayeeBlocked(PayeeBlocked $event): void
    {
        $this->status = PayeeStatus::Blocked;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPayeeUnblocked(PayeeUnblocked $event): void
    {
        $this->status = $event->status();
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->legalName;
    }

    private static function statusFor(string $legalName, ?string $taxId, ?string $iban, ?string $holderName): PayeeStatus
    {
        $isComplete = '' !== trim($legalName)
            && null !== $taxId && '' !== trim($taxId)
            && null !== $iban && '' !== trim($iban)
            && null !== $holderName && '' !== trim($holderName);

        return $isComplete ? PayeeStatus::Ready : PayeeStatus::Incomplete;
    }
}
