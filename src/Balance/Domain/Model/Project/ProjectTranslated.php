<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ProjectTranslated extends DomainEvent
{
    protected static string $eventName = 'projectTranslated';

    public function __construct(
        private ProjectId $projectId,
        private string $name,
        private string $description,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
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
        return $this->projectId();
    }

    public function payload(): array
    {
        return [
            'projectId' => (string) $this->projectId,
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
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new ProjectTranslated(
            projectId: new ProjectId($payload['projectId']),
            name: $payload['name'],
            description: $payload['description'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
