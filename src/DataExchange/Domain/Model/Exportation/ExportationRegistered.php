<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExportationRegistered extends DomainEvent
{
    protected static string $eventName = 'exportationRegistered';

    /**
     * @param string[]             $columns
     * @param array<string, mixed> $filterCriteria
     */
    public function __construct(
        private readonly ExportationId $exportationId,
        private readonly string $type,
        private readonly ExportationFormat $format,
        private readonly array $columns,
        private readonly array $filterCriteria,
        private readonly \DateTimeImmutable $validUntil,
        private readonly ?AccountId $accountId,
        private readonly ?UserId $registeredBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function exportationId(): ExportationId
    {
        return $this->exportationId;
    }

    public function type(): string
    {
        return $this->type;
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

    public function validUntil(): \DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->exportationId;
    }

    public function payload(): array
    {
        return [
            'exportationId' => (string) $this->exportationId,
            'type' => $this->type,
            'format' => $this->format->value,
            'columns' => $this->columns,
            'filterCriteria' => $this->filterCriteria,
            'validUntil' => $this->validUntil->format(\DateTimeInterface::ATOM),
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'registeredBy' => $this->registeredBy instanceof UserId ? (string) $this->registeredBy : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ExportationRegistered(
            exportationId: new ExportationId($payload['exportationId']),
            type: $payload['type'],
            format: ExportationFormat::from($payload['format']),
            columns: $payload['columns'],
            filterCriteria: $payload['filterCriteria'],
            validUntil: new \DateTimeImmutable($payload['validUntil']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            registeredBy: null !== $payload['registeredBy'] ? new UserId($payload['registeredBy']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
