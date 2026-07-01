<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class Material implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MaterialEmissionFactor>|Collection<int, MaterialEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialId $id,
        private string $name = '',
        private MaterialCategory $category = MaterialCategory::Others,
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

    public function id(): MaterialId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var MaterialTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function category(): MaterialCategory
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

    public function emissionFactors(): MaterialEmissionFactors
    {
        return new MaterialEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?MaterialEmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId);
    }

    public function register(
        string $name,
        MaterialCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MaterialRegistered(
                materialId: $this->id(),
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
        MaterialCategory $category,
        Unit $unit,
        ?string $unitWeight = null,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new MaterialUpdated(
                materialId: $this->id(),
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
            new MaterialTranslated(
                materialId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MaterialRemoved(
                materialId: $this->id(),
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
                new MaterialEmissionFactorAdded(
                    materialId: $this->id(),
                    emissionFactorId: MaterialEmissionFactorId::generate(),
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
            new MaterialEmissionFactorUpdated(
                materialId: $this->id(),
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
            new MaterialEmissionFactorRemoved(
                materialId: $this->id(),
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
     * the material's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $materialEmissionFactor) {
            $newFactor = $materialEmissionFactor->newFactor();
            $recycledFactor = $materialEmissionFactor->recycledFactor();

            if ($newFactor->unit() === $unit && $recycledFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($newFactor->withUnit($unit), $recycledFactor->withUnit($unit));
        }
    }

    public function applyMaterialRegistered(MaterialRegistered $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialUpdated(MaterialUpdated $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->unitWeight = $event->unitWeight();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialTranslated(MaterialTranslated $event): void
    {
        $materialTranslation = new MaterialTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($materialTranslation);
    }

    public function applyMaterialRemoved(MaterialRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMaterialEmissionFactorAdded(MaterialEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new MaterialEmissionFactor(
                id: $event->emissionFactorId(),
                material: $this,
                newFactor: $event->newFactor(),
                recycledFactor: $event->recycledFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMaterialEmissionFactorUpdated(MaterialEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->newFactor()->year(), $event->newFactor()->emissionFactorGroupId())
            ?->update($event->newFactor(), $event->recycledFactor(), $event->occurredOn());
    }

    public function applyMaterialEmissionFactorRemoved(MaterialEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
