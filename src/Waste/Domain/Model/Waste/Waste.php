<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Waste implements Translatable, \Stringable
{
    use AggregateRoot;

    private ?Translations $translations = null;

    public function __construct(
        protected WasteId $id,
        protected string $name = '',
        protected bool $isDangerous = false,
        protected string $density = '0.00',
        protected string $color = '#c3d300',
        protected bool $removed = false,
        array $events = [],
        protected ?\DateTimeImmutable $registeredAt = null,
        protected ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
    }

    public function id(): WasteId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var WasteTranslation $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function isDangerous(): bool
    {
        return $this->isDangerous;
    }

    public function density(): string
    {
        return $this->density;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function translations(): Translations
    {
        if (!$this->translations instanceof Translations) {
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

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        bool $isDangerous = false,
        string $density = '0.00',
        string $color = '#c3d300',
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new WasteRegistered(
                wasteId: $this->id(),
                name: $name,
                isDangerous: $isDangerous,
                density: $density,
                color: $color,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        bool $isDangerous,
        string $density,
        string $color,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new WasteUpdated(
                wasteId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                isDangerous: $isDangerous,
                density: $density,
                color: $color,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new WasteTranslated(
                wasteId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new WasteRemoved(
                wasteId: $this->id(),
            ),
        );
    }

    public function applyWasteRegistered(WasteRegistered $event): void
    {
        $this->name = $event->name();
        $this->isDangerous = $event->isDangerous();
        $this->density = $event->density();
        $this->color = $event->color();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteUpdated(WasteUpdated $event): void
    {
        $this->name = $event->name();
        $this->isDangerous = $event->isDangerous();
        $this->density = $event->density();
        $this->color = $event->color();
    }

    public function applyWasteTranslated(WasteTranslated $event): void
    {
        $wasteTranslation = new WasteTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatableId: $this->id(),
        );

        $this->translations()->add($wasteTranslation);
    }

    public function applyWasteRemoved(WasteRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
