<?php

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class Importation
{
    use AggregateRoot;

    public function __construct(
        protected ImportationId $id,
        protected string $name = '',
        protected ?AccountId $accountId = null,
        protected ?ImportationFile $file = null,
        array $events = [],
        protected array $configurations = [],
        protected array $defaultValues = [],
        protected ?\DateTimeImmutable $registeredAt = null,
        protected ?UserId $registeredBy = null,
    ) {

    }

    public function id(): ImportationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function loadConfigurations(array $configurations): void
    {
        if (isset($this->configurations) && is_array($this->configurations) && count($this->configurations) > 0) {
            throw new \RuntimeException('Use configure() to save new configurations.');
        }

        $this->configurations = $configurations;
    }

    public function configurations(): array
    {
        return $this->configurations ?? [];
    }

    public function configurationFor(string $columnName): ?ImportationColumnConfiguration
    {
        if (count($this->configurations()) === 0) {
            return null;
        }

        foreach ($this->configurations() as $configuration) {
            if ($configuration->name === $columnName) {
                return $configuration;
            }
        }

        return null;
    }

    public function configurationForField(string $field): ?ImportationColumnConfiguration
    {
        if (count($this->configurations()) === 0) {
            return null;
        }

        foreach ($this->configurations() as $configuration) {
            if ($configuration->field === $field) {
                return $configuration;
            }
        }

        return null;
    }

    public function defaultValues(): array
    {
        return $this->defaultValues ?? [];
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function file(): ?ImportationFile
    {
        return $this->file ?? null;
    }

    public function registeredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy ?? null;
    }

    public function register(string $name, ?AccountId $accountId, ?UserId $registeredBy = null)
    {
        $this->recordApplyAndPublish(
            new ImportationRegistered(
                importationId: $this->id(),
                name: $name,
                accountId: $accountId,
                registeredBy: $registeredBy,
            )
        );
    }

    public function configure(array $configurations): void
    {
        $this->recordApplyAndPublish(
            new ImportationConfigurationUpdated(
                importationId: $this->id(),
                configurations: $configurations
            )
        );
    }

    public function setDefaultValues(array $defaultValues): void
    {
        $this->recordApplyAndPublish(
            new ImportationDefaultValuesUpdated(
                importationId: $this->id(),
                defaultValues: $defaultValues
            )
        );
    }

    public function applyImportationRegistered(ImportationRegistered $event)
    {
        $this->accountId = $event->accountId();
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
        $this->registeredBy = $event->registeredBy();
    }

    public function applyImportationConfigurationUpdated(ImportationConfigurationUpdated $event)
    {
        $this->configurations = $event->configurations();
    }

    public function applyImportationDefaultValuesUpdated(ImportationDefaultValuesUpdated $event)
    {
        $this->defaultValues = $event->defaultValues();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
