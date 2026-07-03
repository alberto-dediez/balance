<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\ImportationProfile;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ImportationProfileChoicesMemorized extends DomainEvent
{
    protected static string $eventName = 'importationProfileChoicesMemorized';

    /**
     * @param array<string, string>                $fieldsByColumn
     * @param array<string, array<string, string>> $translationsByColumn
     * @param array<string, string>                $defaultValues
     */
    public function __construct(
        private readonly ImportationProfileId $importationProfileId,
        private readonly array $fieldsByColumn = [],
        private readonly array $translationsByColumn = [],
        private readonly array $defaultValues = [],
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function importationProfileId(): ImportationProfileId
    {
        return $this->importationProfileId;
    }

    /** @return array<string, string> */
    public function fieldsByColumn(): array
    {
        return $this->fieldsByColumn;
    }

    /** @return array<string, array<string, string>> */
    public function translationsByColumn(): array
    {
        return $this->translationsByColumn;
    }

    /** @return array<string, string> */
    public function defaultValues(): array
    {
        return $this->defaultValues;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->importationProfileId;
    }

    public function payload(): array
    {
        return [
            'importationProfileId' => (string) $this->importationProfileId,
            'fieldsByColumn' => $this->fieldsByColumn,
            'translationsByColumn' => $this->translationsByColumn,
            'defaultValues' => $this->defaultValues,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ImportationProfileChoicesMemorized(
            importationProfileId: new ImportationProfileId($payload['importationProfileId']),
            fieldsByColumn: $payload['fieldsByColumn'],
            translationsByColumn: $payload['translationsByColumn'],
            defaultValues: $payload['defaultValues'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
