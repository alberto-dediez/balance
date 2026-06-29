<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class GdOFactor implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<GdOFactorTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<GdOFactorEmissionFactor>|Collection<int, GdOFactorEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private GdOFactorId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->emissionFactors = [];
        $this->events = $events;
    }

    public function id(): GdOFactorId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var GdOFactorTranslation|null $translation */
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

    public function emissionFactors(): GdOFactorEmissionFactors
    {
        return new GdOFactorEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year): ?EmissionFactor
    {
        return $this->emissionFactors()->forYear($year)?->emissionFactor();
    }

    public function register(string $name, ?Locale $locale = null): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorRegistered(
                gdOFactorId: $this->id(),
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
            new GdOFactorUpdated(
                gdOFactorId: $this->id(),
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
            new GdOFactorTranslated(
                gdOFactorId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorRemoved(
                gdOFactorId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYear($emissionFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new GdOFactorEmissionFactorAdded(
                    gdOFactorId: $this->id(),
                    emissionFactorId: GdOFactorEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new GdOFactorEmissionFactorUpdated(
                gdOFactorId: $this->id(),
                emissionFactor: $emissionFactor,
            ),
        );
    }

    public function removeEmissionFactor(int $year): void
    {
        if (!$this->emissionFactors()->hasForYear($year)) {
            return;
        }

        $this->recordApplyAndPublish(
            new GdOFactorEmissionFactorRemoved(
                gdOFactorId: $this->id(),
                year: $year,
            ),
        );
    }

    /**
     * Reconciles the stored emission factors with the provided list: adds new ones,
     * updates changed ones and removes the years no longer present.
     *
     * @param EmissionFactor[] $emissionFactors
     */
    public function syncEmissionFactors(array $emissionFactors): void
    {
        $newYears = [];

        foreach ($emissionFactors as $emissionFactor) {
            if (in_array($emissionFactor->year(), $newYears, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate emission factor for year %d', $emissionFactor->year()));
            }

            $newYears[] = $emissionFactor->year();
            $this->setEmissionFactor($emissionFactor);
        }

        foreach ($this->emissionFactors()->years() as $year) {
            if (!in_array($year, $newYears, true)) {
                $this->removeEmissionFactor($year);
            }
        }
    }

    public function applyGdOFactorRegistered(GdOFactorRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyGdOFactorUpdated(GdOFactorUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyGdOFactorTranslated(GdOFactorTranslated $event): void
    {
        $gdOFactorTranslation = new GdOFactorTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($gdOFactorTranslation);
    }

    public function applyGdOFactorRemoved(GdOFactorRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyGdOFactorEmissionFactorAdded(GdOFactorEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new GdOFactorEmissionFactor(
                id: $event->emissionFactorId(),
                gdOFactor: $this,
                emissionFactor: $event->emissionFactor()->withUnit(Unit::KWh),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyGdOFactorEmissionFactorUpdated(GdOFactorEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->emissionFactor()->year())
            ?->update($event->emissionFactor()->withUnit(Unit::KWh), $event->occurredOn());
    }

    public function applyGdOFactorEmissionFactorRemoved(GdOFactorEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
