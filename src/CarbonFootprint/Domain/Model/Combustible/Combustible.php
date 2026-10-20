<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class Combustible implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<CombustibleTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<CombustibleEmissionFactor>|Collection<int, CombustibleEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private CombustibleId $id,
        private string $name = '',
        private Unit $unit = Unit::Kilogram,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): CombustibleId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var CombustibleTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
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

    public function emissionFactors(): CombustibleEmissionFactors
    {
        return new CombustibleEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function wellToTankFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->wellToTankFactor();
    }

    public function register(
        string $name,
        Unit $unit,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new CombustibleRegistered(
                combustibleId: $this->id(),
                name: $name,
                unit: $unit,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new CombustibleUpdated(
                combustibleId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
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
            new CombustibleTranslated(
                combustibleId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CombustibleRemoved(
                combustibleId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor, ?EmissionFactor $wellToTankFactor = null): void
    {
        $wellToTankFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());

        if ($emissionFactor->year() !== $wellToTankFactor->year()) {
            throw new \InvalidArgumentException('Emission factor and well-to-tank factor must share the same year');
        }

        if (!$emissionFactor->emissionFactorGroupId()->equals($wellToTankFactor->emissionFactorGroupId())) {
            throw new \InvalidArgumentException('Emission factor and well-to-tank factor must share the same emission factor group');
        }

        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new CombustibleEmissionFactorAdded(
                    combustibleId: $this->id(),
                    emissionFactorId: CombustibleEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                    wellToTankFactor: $wellToTankFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor) && $current->wellToTankFactor()->equals($wellToTankFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CombustibleEmissionFactorUpdated(
                combustibleId: $this->id(),
                emissionFactor: $emissionFactor,
                wellToTankFactor: $wellToTankFactor,
            ),
        );
    }

    public function removeEmissionFactor(int $year, EmissionFactorGroupId $groupId): void
    {
        if (!$this->emissionFactors()->hasForYearAndGroup($year, $groupId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CombustibleEmissionFactorRemoved(
                combustibleId: $this->id(),
                year: $year,
                emissionFactorGroupId: $groupId,
            ),
        );
    }

    /** @param list<array{factor: EmissionFactor, wellToTank: EmissionFactor}> $factors */
    public function syncEmissionFactors(array $factors): void
    {
        $newKeys = [];

        foreach ($factors as $pair) {
            $year = $pair['factor']->year();
            $key = $year . '|' . $pair['factor']->emissionFactorGroupId();

            if (in_array($key, $newKeys, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate emission factor for year %d', $year));
            }

            $newKeys[] = $key;
            $this->setEmissionFactor($pair['factor'], $pair['wellToTank']);
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
     * the combustible's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $combustibleEmissionFactor) {
            $emissionFactor = $combustibleEmissionFactor->emissionFactor();
            $wellToTankFactor = $combustibleEmissionFactor->wellToTankFactor();

            if ($emissionFactor->unit() === $unit && $wellToTankFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit), $wellToTankFactor->withUnit($unit));
        }
    }

    public function applyCombustibleRegistered(CombustibleRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCombustibleUpdated(CombustibleUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCombustibleTranslated(CombustibleTranslated $event): void
    {
        $combustibleTranslation = new CombustibleTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($combustibleTranslation);
    }

    public function applyCombustibleRemoved(CombustibleRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyCombustibleEmissionFactorAdded(CombustibleEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new CombustibleEmissionFactor(
                id: $event->emissionFactorId(),
                combustible: $this,
                emissionFactor: $event->emissionFactor(),
                wellToTankFactor: $event->wellToTankFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyCombustibleEmissionFactorUpdated(CombustibleEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor(), $event->wellToTankFactor(), $event->occurredOn());
    }

    public function applyCombustibleEmissionFactorRemoved(CombustibleEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
