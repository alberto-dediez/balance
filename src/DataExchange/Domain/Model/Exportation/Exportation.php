<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class Exportation implements \Stringable
{
    use AggregateRoot;

    /**
     * @param string[]                               $columns
     * @param array<string, mixed>                   $filterCriteria
     * @param \App\Shared\Domain\Event\DomainEvent[] $events
     */
    public function __construct(
        protected ExportationId $id,
        protected string $type = '',
        protected ExportationStatus $status = ExportationStatus::Started,
        protected ExportationFormat $format = ExportationFormat::Xlsx,
        protected array $columns = [],
        protected array $filterCriteria = [],
        protected ?AccountId $accountId = null,
        protected StoredFile $file = new StoredFile(),
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected \DateTimeImmutable $validUntil = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $executedAt = null,
        protected ?UserId $registeredBy = null,
        protected ?string $errorMessage = null,
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): ExportationId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function status(): ExportationStatus
    {
        return $this->status;
    }

    public function format(): ExportationFormat
    {
        return $this->format;
    }

    /** @return string[] */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return array<string, mixed> */
    public function filterCriteria(): array
    {
        return $this->filterCriteria;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function file(): ?StoredFile
    {
        return $this->file->isEmpty() ? null : $this->file;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function validUntil(): \DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function executedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt ?? null;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy ?? null;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage ?? null;
    }

    public function isCompleted(): bool
    {
        return ExportationStatus::Completed === $this->status;
    }

    public function isInProgress(): bool
    {
        return ExportationStatus::InProgress === $this->status;
    }

    public function isFailed(): bool
    {
        return ExportationStatus::Failed === $this->status;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->validUntil < $now;
    }

    /**
     * @param string[]             $columns
     * @param array<string, mixed> $filterCriteria
     */
    public function register(
        string $type,
        ExportationFormat $format,
        array $columns,
        array $filterCriteria,
        \DateTimeImmutable $validUntil,
        ?AccountId $accountId,
        ?UserId $registeredBy = null,
    ): void {
        $this->recordApplyAndPublish(
            new ExportationRegistered(
                exportationId: $this->id(),
                type: $type,
                format: $format,
                columns: $columns,
                filterCriteria: $filterCriteria,
                validUntil: $validUntil,
                accountId: $accountId,
                registeredBy: $registeredBy,
            ),
        );
    }

    public function startExecution(): void
    {
        $this->recordApplyAndPublish(
            new ExportationExecutionStarted(
                exportationId: $this->id(),
            ),
        );
    }

    public function completeExecution(StoredFile $file): void
    {
        $this->file = $file;

        $this->recordApplyAndPublish(
            new ExportationExecuted(
                exportationId: $this->id(),
            ),
        );
    }

    public function failExecution(string $errorMessage): void
    {
        $this->recordApplyAndPublish(
            new ExportationFailed(
                exportationId: $this->id(),
                errorMessage: $errorMessage,
            ),
        );
    }

    public function expire(): void
    {
        $this->recordApplyAndPublish(
            new ExportationExpired(
                exportationId: $this->id(),
            ),
        );
    }

    public function applyExportationRegistered(ExportationRegistered $event): void
    {
        $this->type = $event->type();
        $this->format = $event->format();
        $this->columns = $event->columns();
        $this->filterCriteria = $event->filterCriteria();
        $this->validUntil = $event->validUntil();
        $this->accountId = $event->accountId();
        $this->registeredBy = $event->registeredBy();
        $this->status = ExportationStatus::Started;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyExportationExecutionStarted(ExportationExecutionStarted $event): void
    {
        $this->status = ExportationStatus::InProgress;
    }

    public function applyExportationExecuted(ExportationExecuted $event): void
    {
        $this->status = ExportationStatus::Completed;
        $this->executedAt = $event->occurredOn();
    }

    public function applyExportationFailed(ExportationFailed $event): void
    {
        $this->status = ExportationStatus::Failed;
        $this->errorMessage = $event->errorMessage();
    }

    public function applyExportationExpired(ExportationExpired $event): void
    {
        // Marked for removal by the purge process; no state change required.
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
