<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class MachineryFuel implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MachineryFuelTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MachineryFuelEmissionFactor>|Collection<int, MachineryFuelEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MachineryFuelId $id,
        private string $name = '',
        private Unit $unit = Unit::Liter,
        private MachineryType $machineryType = MachineryType::Agricultural,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MachineryFuelId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var MachineryFuelTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function machineryType(): MachineryType
    {
        return $this->machineryType;
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

    public function emissionFactors(): MachineryFuelEmissionFactors
    {
        return new MachineryFuelEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year): ?EmissionFactor
    {
        return $this->emissionFactors()->forYear($year)?->emissionFactor();
    }

    public function register(
        string $name,
        Unit $unit,
        MachineryType $machineryType,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MachineryFuelRegistered(
                machineryFuelId: $this->id(),
                name: $name,
                unit: $unit,
                machineryType: $machineryType,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        MachineryType $machineryType,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new MachineryFuelUpdated(
                machineryFuelId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
                machineryType: $machineryType,
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
            new MachineryFuelTranslated(
                machineryFuelId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MachineryFuelRemoved(
                machineryFuelId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYear($emissionFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new MachineryFuelEmissionFactorAdded(
                    machineryFuelId: $this->id(),
                    emissionFactorId: MachineryFuelEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MachineryFuelEmissionFactorUpdated(
                machineryFuelId: $this->id(),
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
            new MachineryFuelEmissionFactorRemoved(
                machineryFuelId: $this->id(),
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

    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $machineryFuelEmissionFactor) {
            $emissionFactor = $machineryFuelEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyMachineryFuelRegistered(MachineryFuelRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->machineryType = $event->machineryType();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMachineryFuelUpdated(MachineryFuelUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->machineryType = $event->machineryType();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMachineryFuelTranslated(MachineryFuelTranslated $event): void
    {
        $machineryFuelTranslation = new MachineryFuelTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($machineryFuelTranslation);
    }

    public function applyMachineryFuelRemoved(MachineryFuelRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMachineryFuelEmissionFactorAdded(MachineryFuelEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new MachineryFuelEmissionFactor(
                id: $event->emissionFactorId(),
                machineryFuel: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMachineryFuelEmissionFactorUpdated(MachineryFuelEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->emissionFactor()->year())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyMachineryFuelEmissionFactorRemoved(MachineryFuelEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
