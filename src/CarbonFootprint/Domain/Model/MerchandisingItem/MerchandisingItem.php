<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class MerchandisingItem implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MerchandisingItemTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MerchandisingItemEmissionFactor>|Collection<int, MerchandisingItemEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MerchandisingItemId $id,
        private string $name = '',
        private MerchandisingItemCategory $category = MerchandisingItemCategory::Others,
        private Unit $unit = Unit::Kilogram,
        private ?string $unitWeight = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MerchandisingItemId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var MerchandisingItemTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function category(): MerchandisingItemCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function unitWeight(): ?string
    {
        return $this->unitWeight;
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

    public function emissionFactors(): MerchandisingItemEmissionFactors
    {
        return new MerchandisingItemEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year): ?MerchandisingItemEmissionFactor
    {
        return $this->emissionFactors()->forYear($year);
    }

    public function register(
        string $name,
        MerchandisingItemCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MerchandisingItemRegistered(
                merchandisingItemId: $this->id(),
                name: $name,
                category: $category,
                unit: $unit,
                unitWeight: $unitWeight,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        MerchandisingItemCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new MerchandisingItemUpdated(
                merchandisingItemId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                category: $category,
                unit: $unit,
                unitWeight: $unitWeight,
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
            new MerchandisingItemTranslated(
                merchandisingItemId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MerchandisingItemRemoved(
                merchandisingItemId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $newFactor, EmissionFactor $recycledFactor): void
    {
        if ($newFactor->year() !== $recycledFactor->year()) {
            throw new \InvalidArgumentException('New factor and recycled factor must share the same year');
        }

        $current = $this->emissionFactors()->forYear($newFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new MerchandisingItemEmissionFactorAdded(
                    merchandisingItemId: $this->id(),
                    emissionFactorId: MerchandisingItemEmissionFactorId::generate(),
                    newFactor: $newFactor,
                    recycledFactor: $recycledFactor,
                ),
            );

            return;
        }

        if ($current->newFactor()->equals($newFactor) && $current->recycledFactor()->equals($recycledFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MerchandisingItemEmissionFactorUpdated(
                merchandisingItemId: $this->id(),
                newFactor: $newFactor,
                recycledFactor: $recycledFactor,
            ),
        );
    }

    public function removeEmissionFactor(int $year): void
    {
        if (!$this->emissionFactors()->hasForYear($year)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MerchandisingItemEmissionFactorRemoved(
                merchandisingItemId: $this->id(),
                year: $year,
            ),
        );
    }

    /**
     * Reconciles the stored emission factors with the provided list: adds new ones,
     * updates changed ones and removes the years no longer present.
     *
     * @param list<array{new: EmissionFactor, recycled: EmissionFactor}> $factors
     */
    public function syncEmissionFactors(array $factors): void
    {
        $newYears = [];

        foreach ($factors as $pair) {
            $year = $pair['new']->year();

            if (in_array($year, $newYears, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate emission factor for year %d', $year));
            }

            $newYears[] = $year;
            $this->setEmissionFactor($pair['new'], $pair['recycled']);
        }

        foreach ($this->emissionFactors()->years() as $year) {
            if (!in_array($year, $newYears, true)) {
                $this->removeEmissionFactor($year);
            }
        }
    }

    /**
     * Re-stamps the unit of every stored emission factor so it always mirrors
     * the merchandising item's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $merchandisingItemEmissionFactor) {
            $newFactor = $merchandisingItemEmissionFactor->newFactor();
            $recycledFactor = $merchandisingItemEmissionFactor->recycledFactor();

            if ($newFactor->unit() === $unit && $recycledFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($newFactor->withUnit($unit), $recycledFactor->withUnit($unit));
        }
    }

    public function applyMerchandisingItemRegistered(MerchandisingItemRegistered $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMerchandisingItemUpdated(MerchandisingItemUpdated $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMerchandisingItemTranslated(MerchandisingItemTranslated $event): void
    {
        $merchandisingItemTranslation = new MerchandisingItemTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($merchandisingItemTranslation);
    }

    public function applyMerchandisingItemRemoved(MerchandisingItemRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMerchandisingItemEmissionFactorAdded(MerchandisingItemEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new MerchandisingItemEmissionFactor(
                id: $event->emissionFactorId(),
                merchandisingItem: $this,
                newFactor: $event->newFactor(),
                recycledFactor: $event->recycledFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMerchandisingItemEmissionFactorUpdated(MerchandisingItemEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->newFactor()->year())
            ?->update($event->newFactor(), $event->recycledFactor(), $event->occurredOn());
    }

    public function applyMerchandisingItemEmissionFactorRemoved(MerchandisingItemEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
