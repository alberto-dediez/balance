<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ContactRequestContactDataUpdated extends DomainEvent
{
    protected static string $eventName = 'contactRequestContactDataUpdated';

    public function __construct(
        private ContactRequestId $contactRequestId,
        private string $contactName,
        private string $contactEmail,
        private ?string $contactPhone,
        private string $contactCountry,
        private bool $contactIsCompany,
        private ?string $contactCompanyName,
        private ?string $contactVatNumber,
        private ?string $comment,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contactRequestId(): ContactRequestId
    {
        return $this->contactRequestId;
    }

    public function contactName(): string
    {
        return $this->contactName;
    }

    public function contactEmail(): string
    {
        return $this->contactEmail;
    }

    public function contactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function contactCountry(): string
    {
        return $this->contactCountry;
    }

    public function contactIsCompany(): bool
    {
        return $this->contactIsCompany;
    }

    public function contactCompanyName(): ?string
    {
        return $this->contactCompanyName;
    }

    public function contactVatNumber(): ?string
    {
        return $this->contactVatNumber;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contactRequestId();
    }

    public function payload(): array
    {
        return [
            'contactRequestId' => (string) $this->contactRequestId,
            'contactName' => $this->contactName,
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'contactCountry' => $this->contactCountry,
            'contactIsCompany' => $this->contactIsCompany,
            'contactCompanyName' => $this->contactCompanyName,
            'contactVatNumber' => $this->contactVatNumber,
            'comment' => $this->comment,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ContactRequestContactDataUpdated(
            contactRequestId: new ContactRequestId($payload['contactRequestId']),
            contactName: (string) $payload['contactName'],
            contactEmail: (string) $payload['contactEmail'],
            contactPhone: null !== ($payload['contactPhone'] ?? null) ? (string) $payload['contactPhone'] : null,
            contactCountry: (string) $payload['contactCountry'],
            contactIsCompany: (bool) $payload['contactIsCompany'],
            contactCompanyName: null !== ($payload['contactCompanyName'] ?? null) ? (string) $payload['contactCompanyName'] : null,
            contactVatNumber: null !== ($payload['contactVatNumber'] ?? null) ? (string) $payload['contactVatNumber'] : null,
            comment: null !== ($payload['comment'] ?? null) ? (string) $payload['comment'] : null,
            locale: Locale::tryFrom((string) ($payload['locale'] ?? '')) ?? Locale::Spanish,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
