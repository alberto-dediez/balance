<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;

class RecipientOrganization implements \Stringable
{
    use AggregateRoot;

    /** @var string[] */
    private array $pickupTypes = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private RecipientOrganizationId $id,
        private string $name = '',
        private RecipientOrganizationType $type = RecipientOrganizationType::FoodBank,
        private string $nif = '',
        private string $socialReason = '',
        private string $address = '',
        private Contact $contact = new Contact(),
        private string $operatingHours = '',
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): RecipientOrganizationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): RecipientOrganizationType
    {
        return $this->type;
    }

    public function nif(): string
    {
        return $this->nif;
    }

    public function socialReason(): string
    {
        return $this->socialReason;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function operatingHours(): string
    {
        return $this->operatingHours;
    }

    /** @return PickupType[] */
    public function pickupTypes(): array
    {
        return array_map(PickupType::from(...), $this->pickupTypes);
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    /** @param PickupType[] $pickupTypes */
    public function register(
        string $name,
        RecipientOrganizationType $type,
        string $nif,
        string $socialReason,
        string $address,
        string $contactName,
        string $contactEmail,
        string $contactPhone,
        ?Locale $contactLocale,
        NotificationChannel $contactNotificationChannel,
        string $operatingHours,
        array $pickupTypes,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new RecipientOrganizationRegistered(
                recipientOrganizationId: $this->id(),
                name: $name,
                type: $type,
                nif: $nif,
                socialReason: $socialReason,
                address: $address,
                contactName: $contactName,
                contactEmail: $contactEmail,
                contactPhone: $contactPhone,
                contactLocale: $contactLocale,
                contactNotificationChannel: $contactNotificationChannel,
                operatingHours: $operatingHours,
                pickupTypes: array_map(fn(PickupType $p) => $p->value, $pickupTypes),
                accountId: $accountId,
            ),
        );
    }

    /** @param PickupType[] $pickupTypes */
    public function update(
        string $name,
        RecipientOrganizationType $type,
        string $nif,
        string $socialReason,
        string $address,
        string $contactName,
        string $contactEmail,
        string $contactPhone,
        ?Locale $contactLocale,
        NotificationChannel $contactNotificationChannel,
        string $operatingHours,
        array $pickupTypes,
    ): void {
        $this->recordApplyAndPublish(
            new RecipientOrganizationUpdated(
                recipientOrganizationId: $this->id(),
                name: $name,
                type: $type,
                nif: $nif,
                socialReason: $socialReason,
                address: $address,
                contactName: $contactName,
                contactEmail: $contactEmail,
                contactPhone: $contactPhone,
                contactLocale: $contactLocale,
                contactNotificationChannel: $contactNotificationChannel,
                operatingHours: $operatingHours,
                pickupTypes: array_map(fn(PickupType $p) => $p->value, $pickupTypes),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new RecipientOrganizationRemoved(
                recipientOrganizationId: $this->id(),
            ),
        );
    }

    public function applyRecipientOrganizationRegistered(RecipientOrganizationRegistered $event): void
    {
        $this->name = $event->name();
        $this->type = $event->type();
        $this->nif = $event->nif();
        $this->socialReason = $event->socialReason();
        $this->address = $event->address();
        $this->contact = new Contact(
            name: $event->contactName(),
            email: $event->contactEmail(),
            phone: $event->contactPhone(),
            preferredLocale: $event->contactLocale(),
            preferredNotificationChannel: $event->contactNotificationChannel(),
        );
        $this->operatingHours = $event->operatingHours();
        $this->pickupTypes = $event->pickupTypes();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyRecipientOrganizationUpdated(RecipientOrganizationUpdated $event): void
    {
        $this->name = $event->name();
        $this->type = $event->type();
        $this->nif = $event->nif();
        $this->socialReason = $event->socialReason();
        $this->address = $event->address();
        $this->contact = new Contact(
            name: $event->contactName(),
            email: $event->contactEmail(),
            phone: $event->contactPhone(),
            preferredLocale: $event->contactLocale(),
            preferredNotificationChannel: $event->contactNotificationChannel(),
        );
        $this->operatingHours = $event->operatingHours();
        $this->pickupTypes = $event->pickupTypes();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyRecipientOrganizationRemoved(RecipientOrganizationRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
