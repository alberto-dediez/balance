<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ContactRequestRegistered extends DomainEvent
{
    protected static string $eventName = 'contactRequestRegistered';

    public function __construct(
        private ContactRequestId $contactRequestId,
        private AccountId $accountId,
        private ?PropertyId $propertyId,
        private ProjectContributionOrigin $origin,
        private string $originId,
        private ?string $originName,
        private float $co2eqKg,
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

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function origin(): ProjectContributionOrigin
    {
        return $this->origin;
    }

    public function originId(): string
    {
        return $this->originId;
    }

    public function originName(): ?string
    {
        return $this->originName;
    }

    public function co2eqKg(): float
    {
        return $this->co2eqKg;
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
            'accountId' => (string) $this->accountId,
            'propertyId' => null !== $this->propertyId ? (string) $this->propertyId : null,
            'origin' => $this->origin->value,
            'originId' => $this->originId,
            'originName' => $this->originName,
            'co2eqKg' => $this->co2eqKg,
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
        return new ContactRequestRegistered(
            contactRequestId: new ContactRequestId($payload['contactRequestId']),
            accountId: new AccountId($payload['accountId']),
            propertyId: null !== ($payload['propertyId'] ?? null) ? new PropertyId($payload['propertyId']) : null,
            origin: ProjectContributionOrigin::from($payload['origin']),
            originId: (string) $payload['originId'],
            originName: null !== ($payload['originName'] ?? null) ? (string) $payload['originName'] : null,
            co2eqKg: (float) $payload['co2eqKg'],
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
