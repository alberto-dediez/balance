<?php

namespace App\FoodLoss\Domain\Model\Action;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Action implements Translatable
{
    use AggregateRoot;

    private ?Translations $translations = null;

    public function __construct(
        private ActionId $id,
        private string $description = '',
        private Priority $priority = Priority::Prevention,
        private bool $removed = false,
        array $events = [],
        private ?\DateTimeImmutable $registeredAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null
    ) {
        $this->translations = new Translations();
    }

    public function id(): ActionId
    {
        return $this->id;
    }

    public function description(): string
    {
        /** @var ActionTranslation $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function translations(): Translations
    {
        if (null === $this->translations) {
            $this->translations = new Translations();
        }

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
        ?Locale $locale = null
    ) {
        $this->recordApplyAndPublish(
            new ActionRegistered(
                actionId: $this->id(),
                description: $description,
                priority: $priority
            )
        );

        if (null !== $locale) {
            $this->translate($description, $locale);
        }
    }

    public function update(
        string $description,
        Priority $priority,
        ?Locale $locale = null
    ) {
        $this->recordApplyAndPublish(
            new ActionUpdated(
                actionId: $this->id(),
                description: null === $locale ? $description : $this->description,
                priority: $priority
            )
        );

        if (null !== $locale) {
            $this->translate($description, $locale);
        }
    }

    public function translate(string $description, Locale $locale)
    {
        $this->recordApplyAndPublish(
            new ActionTranslated(
                actionId: $this->id(),
                description: $description,
                locale: $locale
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new ActionRemoved(
                actionId: $this->id()
            )
        );
    }

    public function applyActionRegistered(ActionRegistered $event)
    {
        $this->description = $event->description();
        $this->priority = $event->priority();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyActionUpdated(ActionUpdated $event)
    {
        $this->description = $event->description();
        $this->priority = $event->priority();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyActionTranslated(ActionTranslated $event)
    {
        $actionTranslation = new ActionTranslation(
            description: $event->description(),
            locale: $event->locale(),
            translatableId: $this->id()
        );

        $this->translations()->add($actionTranslation);
    }

    public function applyActionRemoved(ActionRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->description();
    }
}