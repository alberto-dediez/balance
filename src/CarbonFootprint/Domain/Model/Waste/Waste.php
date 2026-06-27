<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class Waste implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<WasteTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<WasteEmissionFactor>|Collection<int, WasteEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private WasteId $id,
        private string $name = '',
        private Unit $unit = Unit::Kilogram,
        private float $density = 1.0,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): WasteId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var WasteTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function density(): float
    {
        return $this->density;
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

    public function emissionFactors(): WasteEmissionFactors
    {
        return new WasteEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year): ?EmissionFactor
    {
        return $this->emissionFactors()->forYear($year)?->emissionFactor();
    }

    public function register(
        string $name,
        Unit $unit,
        float $density,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new WasteRegistered(
                wasteId: $this->id(),
                name: $name,
                unit: $unit,
                density: $density,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        float $density,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new WasteUpdated(
                wasteId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
                density: $density,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }

        if ($previousUnit !== $unit) {
            $this->restampEmissionFactorUnits($unit);
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

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYear($emissionFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new WasteEmissionFactorAdded(
                    wasteId: $this->id(),
                    emissionFactorId: WasteEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new WasteEmissionFactorUpdated(
                wasteId: $this->id(),
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
            new WasteEmissionFactorRemoved(
                wasteId: $this->id(),
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

    /**
     * Re-stamps the unit of every stored emission factor so it always mirrors
     * the waste's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $wasteEmissionFactor) {
            $emissionFactor = $wasteEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyWasteRegistered(WasteRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->density = $event->density();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteUpdated(WasteUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->density = $event->density();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyWasteTranslated(WasteTranslated $event): void
    {
        $wasteTranslation = new WasteTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($wasteTranslation);
    }

    public function applyWasteRemoved(WasteRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyWasteEmissionFactorAdded(WasteEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new WasteEmissionFactor(
                id: $event->emissionFactorId(),
                waste: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyWasteEmissionFactorUpdated(WasteEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->emissionFactor()->year())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyWasteEmissionFactorRemoved(WasteEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
