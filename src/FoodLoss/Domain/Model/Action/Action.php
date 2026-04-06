<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Action implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<ActionTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private ActionId $id,
        private string $description = '',
        private Priority $priority = Priority::Prevention,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();

        $this->events = $events;
    }

    public function id(): ActionId
    {
        return $this->id;
    }

    public function description(): string
    {
        /** @var ActionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $description,
        Priority $priority,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ActionRegistered(
                actionId: $this->id(),
                description: $description,
                priority: $priority,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($description, $locale);
        }
    }

    public function update(
        string $description,
        Priority $priority,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ActionUpdated(
                actionId: $this->id(),
                description: $locale instanceof Locale ? $this->description : $description,
                priority: $priority,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($description, $locale);
        }
    }

    public function translate(string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new ActionTranslated(
                actionId: $this->id(),
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ActionRemoved(
                actionId: $this->id(),
            ),
        );
    }

    public function applyActionRegistered(ActionRegistered $event): void
    {
        $this->description = $event->description();
        $this->priority = $event->priority();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyActionUpdated(ActionUpdated $event): void
    {
        $this->description = $event->description();
        $this->priority = $event->priority();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyActionTranslated(ActionTranslated $event): void
    {
        $actionTranslation = new ActionTranslation(
            description: $event->description(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($actionTranslation);
    }

    public function applyActionRemoved(ActionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->description();
    }
}
