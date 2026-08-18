<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRootId;

final class PayeeUpdated extends DomainEvent
{
    protected static string $eventName = 'payeeUpdated';

    public function __construct(
        private PayeeId $payeeId,
        private string $legalName,
        private ?string $taxId,
        private Address $address,
        private ?string $iban,
        private ?string $bic,
        private ?string $holderName,
        private ?string $notes,
        private PayeeStatus $status,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payeeId(): PayeeId
    {
        return $this->payeeId;
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

    public function bic(): ?string
    {
        return $this->bic;
    }

    public function holderName(): ?string
    {
        return $this->holderName;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): PayeeStatus
    {
        return $this->status;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payeeId();
    }

    public function payload(): array
    {
        return [
            'payeeId' => (string) $this->payeeId,
            'legalName' => $this->legalName,
            'taxId' => $this->taxId,
            'street' => $this->address->street(),
            'postalCode' => $this->address->postalCode(),
            'city' => $this->address->city(),
            'country' => $this->address->country(),
            'iban' => $this->iban,
            'bic' => $this->bic,
            'holderName' => $this->holderName,
            'notes' => $this->notes,
            'status' => $this->status->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayeeUpdated(
            payeeId: new PayeeId($payload['payeeId']),
            legalName: (string) $payload['legalName'],
            taxId: $payload['taxId'],
            address: new Address(
                street: $payload['street'],
                postalCode: $payload['postalCode'],
                city: $payload['city'],
                country: $payload['country'],
            ),
            iban: $payload['iban'],
            bic: $payload['bic'],
            holderName: $payload['holderName'],
            notes: $payload['notes'],
            status: PayeeStatus::from((string) $payload['status']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
