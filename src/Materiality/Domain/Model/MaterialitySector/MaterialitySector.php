<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class MaterialitySector implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialitySectorTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialitySectorId $id,
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

    public function id(): MaterialitySectorId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var MaterialitySectorTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function defaultName(): string
    {
        return $this->name;
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

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function register(string $name, ?Locale $locale = null): void
    {
        $this->recordApplyAndPublish(
            new MaterialitySectorRegistered(
                materialitySectorId: $this->id(),
                name: $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(string $name, ?Locale $locale = null): void
    {
        $this->recordApplyAndPublish(
            new MaterialitySectorUpdated(
                materialitySectorId: $this->id(),
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
            new MaterialitySectorTranslated(
                materialitySectorId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialitySectorRemoved(
                materialitySectorId: $this->id(),
            ),
        );
    }

    public function applyMaterialitySectorRegistered(MaterialitySectorRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialitySectorUpdated(MaterialitySectorUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialitySectorTranslated(MaterialitySectorTranslated $event): void
    {
        $this->translations()->add(
            new MaterialitySectorTranslation(
                name: $event->name(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyMaterialitySectorRemoved(MaterialitySectorRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
