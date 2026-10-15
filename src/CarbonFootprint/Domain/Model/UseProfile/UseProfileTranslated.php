<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class UseProfileTranslated extends DomainEvent
{
    protected static string $eventName = 'useProfileTranslated';

    public function __construct(
        private UseProfileId $useProfileId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function useProfileId(): UseProfileId
    {
        return $this->useProfileId;
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
        return $this->useProfileId();
    }

    public function payload(): array
    {
        return [
            'useProfileId' => (string) $this->useProfileId,
            'name' => $this->name,
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

        return new UseProfileTranslated(
            useProfileId: new UseProfileId($payload['useProfileId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
