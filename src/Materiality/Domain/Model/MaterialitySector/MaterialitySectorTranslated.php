<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MaterialitySectorTranslated extends DomainEvent
{
    protected static string $eventName = 'materialitySectorTranslated';

    public function __construct(
        private MaterialitySectorId $materialitySectorId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialitySectorId(): MaterialitySectorId
    {
        return $this->materialitySectorId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialitySectorId();
    }

    public function payload(): array
    {
        return [
            'materialitySectorId' => (string) $this->materialitySectorId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialitySectorTranslated(
            materialitySectorId: new MaterialitySectorId($payload['materialitySectorId']),
            name: $payload['name'],
            locale: Locale::from($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
