<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;

class ContactRequest
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private ContactRequestId $id,
        private ?AccountId $accountId = null,
        private ?PropertyId $propertyId = null,
        private ProjectContributionOrigin $origin = ProjectContributionOrigin::AttendeeSurveyResponse,
        private string $originId = '',
        private ?string $originName = null,
        private float $co2eqKg = 0.0,
        private string $contactName = '',
        private string $contactEmail = '',
        private ?string $contactPhone = null,
        private string $contactCountry = '',
        private bool $contactIsCompany = false,
        private ?string $contactCompanyName = null,
        private ?string $contactVatNumber = null,
        private ?string $comment = null,
        private Locale $locale = Locale::Spanish,
        private ContactRequestStatus $status = ContactRequestStatus::Pending,
        array $events = [],
        private \DateTimeImmutable $requestedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $resolvedAt = null,
        private ?UserId $resolvedBy = null,
    ) {
        $this->events = $events;
    }

    public function id(): ContactRequestId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
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

    public function status(): ContactRequestStatus
    {
        return $this->status;
    }

    public function requestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function resolvedBy(): ?UserId
    {
        return $this->resolvedBy;
    }

    public function isPending(): bool
    {
        return ContactRequestStatus::Pending === $this->status;
    }

    public function register(
        AccountId $accountId,
        ?PropertyId $propertyId,
        ProjectContributionOrigin $origin,
        string $originId,
        ?string $originName,
        float $co2eqKg,
        string $contactName,
        string $contactEmail,
        ?string $contactPhone,
        string $contactCountry,
        bool $contactIsCompany,
        ?string $contactCompanyName,
        ?string $contactVatNumber,
        ?string $comment,
        Locale $locale,
        \DateTimeImmutable $requestedAt,
    ): void {
        $this->guardContactData($contactName, $contactEmail);

        if ($co2eqKg < 0.0) {
            throw new \InvalidArgumentException('Contact request footprint cannot be negative.');
        }

        $this->recordApplyAndPublish(
            new ContactRequestRegistered(
                contactRequestId: $this->id(),
                accountId: $accountId,
                propertyId: $propertyId,
                origin: $origin,
                originId: $originId,
                originName: $originName,
                co2eqKg: $co2eqKg,
                contactName: $contactName,
                contactEmail: $contactEmail,
                contactPhone: $contactPhone,
                contactCountry: $contactCountry,
                contactIsCompany: $contactIsCompany,
                contactCompanyName: $contactCompanyName,
                contactVatNumber: $contactVatNumber,
                comment: $comment,
                locale: $locale,
                occurredOn: $requestedAt,
            ),
        );
    }

    public function updateContactData(
        string $contactName,
        string $contactEmail,
        ?string $contactPhone,
        string $contactCountry,
        bool $contactIsCompany,
        ?string $contactCompanyName,
        ?string $contactVatNumber,
        ?string $comment,
        Locale $locale,
    ): void {
        $this->guardPending();
        $this->guardContactData($contactName, $contactEmail);

        $this->recordApplyAndPublish(
            new ContactRequestContactDataUpdated(
                contactRequestId: $this->id(),
                contactName: $contactName,
                contactEmail: $contactEmail,
                contactPhone: $contactPhone,
                contactCountry: $contactCountry,
                contactIsCompany: $contactIsCompany,
                contactCompanyName: $contactCompanyName,
                contactVatNumber: $contactVatNumber,
                comment: $comment,
                locale: $locale,
            ),
        );
    }

    public function markAsManaged(UserId $by, \DateTimeImmutable $at): void
    {
        $this->guardPending();

        $this->recordApplyAndPublish(
            new ContactRequestManaged(
                contactRequestId: $this->id(),
                resolvedBy: $by,
                resolvedAt: $at,
            ),
        );
    }

    public function discard(UserId $by, \DateTimeImmutable $at): void
    {
        $this->guardPending();

        $this->recordApplyAndPublish(
            new ContactRequestDiscarded(
                contactRequestId: $this->id(),
                resolvedBy: $by,
                resolvedAt: $at,
            ),
        );
    }

    public function applyContactRequestRegistered(ContactRequestRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->propertyId = $event->propertyId();
        $this->origin = $event->origin();
        $this->originId = $event->originId();
        $this->originName = $event->originName();
        $this->co2eqKg = $event->co2eqKg();
        $this->contactName = $event->contactName();
        $this->contactEmail = $event->contactEmail();
        $this->contactPhone = $event->contactPhone();
        $this->contactCountry = $event->contactCountry();
        $this->contactIsCompany = $event->contactIsCompany();
        $this->contactCompanyName = $event->contactCompanyName();
        $this->contactVatNumber = $event->contactVatNumber();
        $this->comment = $event->comment();
        $this->locale = $event->locale();
        $this->status = ContactRequestStatus::Pending;
        $this->requestedAt = $event->occurredOn();
        $this->resolvedAt = null;
        $this->resolvedBy = null;
    }

    public function applyContactRequestContactDataUpdated(ContactRequestContactDataUpdated $event): void
    {
        $this->contactName = $event->contactName();
        $this->contactEmail = $event->contactEmail();
        $this->contactPhone = $event->contactPhone();
        $this->contactCountry = $event->contactCountry();
        $this->contactIsCompany = $event->contactIsCompany();
        $this->contactCompanyName = $event->contactCompanyName();
        $this->contactVatNumber = $event->contactVatNumber();
        $this->comment = $event->comment();
        $this->locale = $event->locale();
    }

    public function applyContactRequestManaged(ContactRequestManaged $event): void
    {
        $this->status = ContactRequestStatus::Managed;
        $this->resolvedAt = $event->resolvedAt();
        $this->resolvedBy = $event->resolvedBy();
    }

    public function applyContactRequestDiscarded(ContactRequestDiscarded $event): void
    {
        $this->status = ContactRequestStatus::Discarded;
        $this->resolvedAt = $event->resolvedAt();
        $this->resolvedBy = $event->resolvedBy();
    }

    private function guardPending(): void
    {
        if (!$this->isPending()) {
            throw new ContactRequestAlreadyResolvedException($this->id());
        }
    }

    private function guardContactData(string $contactName, string $contactEmail): void
    {
        if ('' === trim($contactName)) {
            throw new \InvalidArgumentException('Contact request name cannot be empty.');
        }

        if ('' === trim($contactEmail)) {
            throw new \InvalidArgumentException('Contact request email cannot be empty.');
        }

        if (false === filter_var($contactEmail, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Contact request email is not valid.');
        }
    }
}
