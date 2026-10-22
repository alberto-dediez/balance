<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Property implements \Stringable
{
    use AggregateRoot;

    /**
     * @param array<DomainEvent> $events
     */
    public function __construct(
        private PropertyId $id,
        private string $name = '',
        private ?PropertyInfo $info = null,
        private ?ExternalId $externalId = null,
        private ?AccountId $accountId = null,
        private ?CompanyId $companyId = null,
        private ?bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $removedAt = null,
        private ?int $numberOfEmployees = null,
        private ?int $dailyClients = null,
        private OperatingRegime $operatingRegime = OperatingRegime::Owned,
    ) {
        $this->events = $events;
    }

    public function id(): PropertyId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function info(): PropertyInfo
    {
        if (!$this->info instanceof PropertyInfo) {
            $this->info = new PropertyInfo();
        }

        return $this->info;
    }

    public function externalId(): ExternalId
    {
        if (!$this->externalId instanceof ExternalId) {
            $this->externalId = new ExternalId();
        }

        return $this->externalId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function companyId(): ?CompanyId
    {
        return $this->companyId;
    }

    public function numberOfEmployees(): ?int
    {
        return $this->numberOfEmployees;
    }

    public function dailyClients(): ?int
    {
        return $this->dailyClients;
    }

    public function operatingRegime(): OperatingRegime
    {
        return $this->operatingRegime;
    }

    public function isRemoved(): bool
    {
        return $this->removed ?? false;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(string $name, ?AccountId $accountId, ?PropertyInfo $info = null, ?ExternalId $externalId = null, ?CompanyId $companyId = null, ?int $numberOfEmployees = null, ?int $dailyClients = null, OperatingRegime $operatingRegime = OperatingRegime::Owned): void
    {
        $this->recordApplyAndPublish(
            new PropertyRegistered(
                propertyId: $this->id(),
                name: $name,
                info: $info,
                externalId: $externalId,
                accountId: $accountId,
                companyId: $companyId,
                numberOfEmployees: $numberOfEmployees,
                dailyClients: $dailyClients,
                operatingRegime: $operatingRegime,
            ),
        );
    }

    public function update(string $name, ?AccountId $accountId, ?PropertyInfo $info = null, ?ExternalId $externalId = null, ?CompanyId $companyId = null, ?int $numberOfEmployees = null, ?int $dailyClients = null, ?OperatingRegime $operatingRegime = null): void
    {
        $this->recordApplyAndPublish(
            new PropertyUpdated(
                propertyId: $this->id(),
                name: $name,
                info: $info,
                externalId: $externalId,
                accountId: $accountId,
                companyId: $companyId,
                numberOfEmployees: $numberOfEmployees,
                dailyClients: $dailyClients,
                operatingRegime: $operatingRegime ?? $this->operatingRegime,
            ),
        );
    }

    public function match(string $name, PropertyInfo $info, ExternalId $externalId): void
    {
        $this->recordApplyAndPublish(
            new PropertyMatched(
                propertyId: $this->id(),
                name: $name,
                info: $info,
                externalId: $externalId,
            ),
        );
    }

    public function releaseAccount(): void
    {
        if (!$this->accountId instanceof AccountId) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyAccountReleased(
                propertyId: $this->id(),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PropertyRemoved(
                propertyId: $this->id(),
            ),
        );
    }

    public function applyPropertyRegistered(PropertyRegistered $event): void
    {
        $this->name = $event->name();
        $this->accountId = $event->accountId();
        $this->companyId = $event->companyId();

        $this->info = $event->info() ?? new PropertyInfo();
        $this->externalId = $event->externalId() ?? new ExternalId();

        $this->numberOfEmployees = $event->numberOfEmployees();
        $this->dailyClients = $event->dailyClients();
        $this->operatingRegime = $event->operatingRegime();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyPropertyMatched(PropertyMatched $event): void
    {
        $eventInfo = $event->info() ?? new PropertyInfo();
        $eventExternalId = $event->externalId() ?? new ExternalId();

        if (!$this->info()->equals($eventInfo) || !$this->externalId()->equals($eventExternalId)) {
            $this->recordApplyAndPublish(
                new PropertyUpdated(
                    propertyId: $this->id(),
                    name: $this->name(),
                    info: $this->info()->combine($eventInfo),
                    externalId: $this->externalId()->combine($eventExternalId),
                    accountId: $this->accountId(),
                    companyId: $this->companyId(),
                    numberOfEmployees: $this->numberOfEmployees(),
                    dailyClients: $this->dailyClients(),
                    operatingRegime: $this->operatingRegime(),
                ),
            );
        }
    }

    public function applyPropertyUpdated(PropertyUpdated $event): void
    {
        $this->name = $event->name();
        $this->accountId = $event->accountId();
        $this->companyId = $event->companyId();

        $this->info = $event->info() ?? $this->info();
        $this->externalId = $event->externalId() ?? $this->externalId();

        $this->numberOfEmployees = $event->numberOfEmployees();
        $this->dailyClients = $event->dailyClients();
        $this->operatingRegime = $event->operatingRegime();
    }

    public function applyPropertyAccountReleased(PropertyAccountReleased $event): void
    {
        $this->accountId = null;
    }

    public function applyPropertyRemoved(PropertyRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
