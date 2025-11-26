<?php

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
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
        protected ?\DateTimeImmutable $registeredAt = null,
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

    public function register(string $name, ?AccountId $accountId)
    {
        $this->recordApplyAndPublish(
            new ImportationRegistered(
                importationId: $this->id(),
                name: $name,
                accountId: $accountId
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

    public function applyImportationRegistered(ImportationRegistered $event)
    {
        $this->accountId = $event->accountId();
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyImportationConfigurationUpdated(ImportationConfigurationUpdated $event)
    {
        $this->configurations = $event->configurations();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
