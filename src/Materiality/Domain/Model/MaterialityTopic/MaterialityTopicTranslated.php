<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MaterialityTopicTranslated extends DomainEvent
{
    protected static string $eventName = 'materialityTopicTranslated';

    public function __construct(
        private MaterialityTopicId $materialityTopicId,
        private string $name,
        private string $description,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityTopicId(): MaterialityTopicId
    {
        return $this->materialityTopicId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityTopicId();
    }

    public function payload(): array
    {
        return [
            'materialityTopicId' => (string) $this->materialityTopicId,
            'name' => $this->name,
            'description' => $this->description,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityTopicTranslated(
            materialityTopicId: new MaterialityTopicId($payload['materialityTopicId']),
            name: $payload['name'],
            description: $payload['description'],
            locale: Locale::from($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
