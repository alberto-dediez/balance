<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class Importation implements \Stringable
{
    use AggregateRoot;

    /** @var ImportationColumnConfiguration[] */
    protected array $configurations = [];

    /**
     * @param ImportationColumnConfiguration[] $configurations
     * @param array<string, mixed>             $events
     * @param array<string, mixed>             $defaultValues
     */
    public function __construct(
        protected ImportationId $id,
        protected string $name = '',
        protected string $type = '',
        protected ImportationStatus $status = ImportationStatus::Started,
        protected ?AccountId $accountId = null,
        protected ?ImportationFile $file = null,
        array $events = [],
        array $configurations = [],
        protected array $defaultValues = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $executedAt = null,
        protected ?UserId $registeredBy = null,
    ) {
        $this->events = $events;
        $this->configurations = $configurations;
    }

    public function id(): ImportationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isExecuted(): bool
    {
        return ImportationStatus::Executed === $this->status;
    }

    public function isInProgress(): bool
    {
        return ImportationStatus::InProgress === $this->status;
    }

    /** @param ImportationColumnConfiguration[] $configurations */
    public function loadConfigurations(array $configurations): void
    {
        if (count($this->configurations) > 0) {
            throw new \RuntimeException('Use configure() to save new configurations.');
        }

        $this->configurations = $configurations;
    }

    /** @return ImportationColumnConfiguration[] */
    public function configurations(): array
    {
        return $this->configurations;
    }

    public function configurationFor(string $columnName): ?ImportationColumnConfiguration
    {
        if (0 === count($this->configurations())) {
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
        if (0 === count($this->configurations())) {
            return null;
        }

        foreach ($this->configurations() as $configuration) {
            if ($configuration->field === $field) {
                return $configuration;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function defaultValues(): array
    {
        return $this->defaultValues;
    }

    public function defaultValueForField(string $field): ?string
    {
        return $this->defaultValues[$field] ?? null;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function file(): ?ImportationFile
    {
        return $this->file ?? null;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy ?? null;
    }

    public function register(string $name, string $type, ?AccountId $accountId, ?UserId $registeredBy = null): void
    {
        $this->recordApplyAndPublish(
            new ImportationRegistered(
                importationId: $this->id(),
                name: $name,
                type: $type,
                accountId: $accountId,
                registeredBy: $registeredBy,
            ),
        );
    }

    /** @param ImportationColumnConfiguration[] $configurations */
    public function configure(array $configurations): void
    {
        $this->recordApplyAndPublish(
            new ImportationConfigurationUpdated(
                importationId: $this->id(),
                configurations: $configurations,
            ),
        );
    }

    /** @param array<string, mixed> $defaultValues */
    public function setDefaultValues(array $defaultValues): void
    {
        $this->recordApplyAndPublish(
            new ImportationDefaultValuesUpdated(
                importationId: $this->id(),
                defaultValues: $defaultValues,
            ),
        );
    }

    public function startExecution(): void
    {
        $this->recordApplyAndPublish(
            new ImportationExecutionStarted(
                importationId: $this->id(),
            ),
        );
    }

    public function finishExecution(): void
    {
        $this->recordApplyAndPublish(
            new ImportationExecuted(
                importationId: $this->id(),
            ),
        );
    }

    public function applyImportationRegistered(ImportationRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->name = $event->name();
        $this->type = $event->type();
        $this->status = ImportationStatus::Started;
        $this->registeredAt = $event->occurredOn();
        $this->registeredBy = $event->registeredBy();
    }

    public function applyImportationConfigurationUpdated(ImportationConfigurationUpdated $event): void
    {
        $this->configurations = $event->configurations();
    }

    public function applyImportationDefaultValuesUpdated(ImportationDefaultValuesUpdated $event): void
    {
        $this->defaultValues = $event->defaultValues();
    }

    public function applyImportationExecutionStarted(ImportationExecutionStarted $event): void
    {
        $this->status = ImportationStatus::InProgress;
    }

    public function applyImportationExecuted(ImportationExecuted $event): void
    {
        $this->status = ImportationStatus::Executed;
        $this->executedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
