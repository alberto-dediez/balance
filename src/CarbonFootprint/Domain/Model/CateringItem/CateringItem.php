<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class CateringItem implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<CateringItemTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<CateringItemEmissionFactor>|Collection<int, CateringItemEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private CateringItemId $id,
        private string $name = '',
        private CateringItemCategory $category = CateringItemCategory::Others,
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

    public function id(): CateringItemId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var CateringItemTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function category(): CateringItemCategory
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

    public function emissionFactors(): CateringItemEmissionFactors
    {
        return new CateringItemEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?CateringItemEmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId);
    }

    public function register(
        string $name,
        CateringItemCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new CateringItemRegistered(
                cateringItemId: $this->id(),
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
        CateringItemCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new CateringItemUpdated(
                cateringItemId: $this->id(),
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
            new CateringItemTranslated(
                cateringItemId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CateringItemRemoved(
                cateringItemId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $newFactor, EmissionFactor $recycledFactor): void
    {
        if ($newFactor->year() !== $recycledFactor->year()) {
            throw new \InvalidArgumentException('New factor and recycled factor must share the same year');
        }

        if (!$newFactor->emissionFactorGroupId()->equals($recycledFactor->emissionFactorGroupId())) {
            throw new \InvalidArgumentException('New factor and recycled factor must share the same emission factor group');
        }

        $current = $this->emissionFactors()->forYearAndGroup($newFactor->year(), $newFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new CateringItemEmissionFactorAdded(
                    cateringItemId: $this->id(),
                    emissionFactorId: CateringItemEmissionFactorId::generate(),
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
            new CateringItemEmissionFactorUpdated(
                cateringItemId: $this->id(),
                newFactor: $newFactor,
                recycledFactor: $recycledFactor,
            ),
        );
    }

    public function removeEmissionFactor(int $year, EmissionFactorGroupId $groupId): void
    {
        if (!$this->emissionFactors()->hasForYearAndGroup($year, $groupId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CateringItemEmissionFactorRemoved(
                cateringItemId: $this->id(),
                year: $year,
                emissionFactorGroupId: $groupId,
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
        $newKeys = [];

        foreach ($factors as $pair) {
            $year = $pair['new']->year();
            $key = $year . '|' . $pair['new']->emissionFactorGroupId();

            if (in_array($key, $newKeys, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate emission factor for year %d', $year));
            }

            $newKeys[] = $key;
            $this->setEmissionFactor($pair['new'], $pair['recycled']);
        }

        foreach ($this->emissionFactors() as $existingFactor) {
            $key = $existingFactor->year() . '|' . $existingFactor->emissionFactorGroupId();

            if (!in_array($key, $newKeys, true)) {
                $this->removeEmissionFactor($existingFactor->year(), $existingFactor->emissionFactorGroupId());
            }
        }
    }

    /**
     * Re-stamps the unit of every stored emission factor so it always mirrors
     * the catering item's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $cateringItemEmissionFactor) {
            $newFactor = $cateringItemEmissionFactor->newFactor();
            $recycledFactor = $cateringItemEmissionFactor->recycledFactor();

            if ($newFactor->unit() === $unit && $recycledFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($newFactor->withUnit($unit), $recycledFactor->withUnit($unit));
        }
    }

    public function applyCateringItemRegistered(CateringItemRegistered $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCateringItemUpdated(CateringItemUpdated $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCateringItemTranslated(CateringItemTranslated $event): void
    {
        $cateringItemTranslation = new CateringItemTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($cateringItemTranslation);
    }

    public function applyCateringItemRemoved(CateringItemRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyCateringItemEmissionFactorAdded(CateringItemEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new CateringItemEmissionFactor(
                id: $event->emissionFactorId(),
                cateringItem: $this,
                newFactor: $event->newFactor(),
                recycledFactor: $event->recycledFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyCateringItemEmissionFactorUpdated(CateringItemEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->newFactor()->year(), $event->newFactor()->emissionFactorGroupId())
            ?->update($event->newFactor(), $event->recycledFactor(), $event->occurredOn());
    }

    public function applyCateringItemEmissionFactorRemoved(CateringItemEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
