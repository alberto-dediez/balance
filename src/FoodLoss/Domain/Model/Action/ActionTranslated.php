<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ActionTranslated extends DomainEvent
{
    protected static string $eventName = 'actionTranslated';

    public function __construct(
        private readonly ActionId $actionId,
        private readonly string $description,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
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
        return $this->actionId;
    }

    public function payload(): array
    {
        return [
            'actionId' => (string) $this->actionId,
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

        return new ActionTranslated(
            actionId: new ActionId($payload['actionId']),
            description: $payload['description'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
