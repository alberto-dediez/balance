<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Reason implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<ReasonTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private ReasonId $id,
        private string $description = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();

        $this->events = $events;
    }

    public function id(): ReasonId
    {
        return $this->id;
    }

    public function description(): string
    {
        /** @var ReasonTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
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
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ReasonRegistered(
                reasonId: $this->id(),
                description: $description,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($description, $locale);
        }
    }

    public function update(
        string $description,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ReasonUpdated(
                reasonId: $this->id(),
                description: $locale instanceof Locale ? $this->description : $description,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($description, $locale);
        }
    }

    public function translate(string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new ReasonTranslated(
                reasonId: $this->id(),
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ReasonRemoved(
                reasonId: $this->id(),
            ),
        );
    }

    public function applyReasonRegistered(ReasonRegistered $event): void
    {
        $this->description = $event->description();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyReasonUpdated(ReasonUpdated $event): void
    {
        $this->description = $event->description();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyReasonTranslated(ReasonTranslated $event): void
    {
        $reasonTranslation = new ReasonTranslation(
            description: $event->description(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($reasonTranslation);
    }

    public function applyReasonRemoved(ReasonRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->description();
    }
}
