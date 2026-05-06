<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class SurplusCause implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<SurplusCauseTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private SurplusCauseId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();

        $this->events = $events;
    }

    public function id(): SurplusCauseId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var SurplusCauseTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
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
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusCauseRegistered(
                surplusCauseId: $this->id(),
                name: $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusCauseUpdated(
                surplusCauseId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new SurplusCauseTranslated(
                surplusCauseId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new SurplusCauseRemoved(
                surplusCauseId: $this->id(),
            ),
        );
    }

    public function applySurplusCauseRegistered(SurplusCauseRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applySurplusCauseUpdated(SurplusCauseUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusCauseTranslated(SurplusCauseTranslated $event): void
    {
        $surplusCauseTranslation = new SurplusCauseTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($surplusCauseTranslation);
    }

    public function applySurplusCauseRemoved(SurplusCauseRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
