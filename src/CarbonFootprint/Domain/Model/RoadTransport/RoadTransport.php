<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class RoadTransport implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<RoadTransportTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<RoadTransportEmissionFactor>|Collection<int, RoadTransportEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private RoadTransportId $id,
        private string $name = '',
        private Unit $unit = Unit::Kilogram,
        private Vehicle $vehicle = Vehicle::PassengerCars,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): RoadTransportId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var RoadTransportTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function vehicle(): Vehicle
    {
        return $this->vehicle;
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

    public function emissionFactors(): RoadTransportEmissionFactors
    {
        return new RoadTransportEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function register(
        string $name,
        Unit $unit,
        Vehicle $vehicle,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new RoadTransportRegistered(
                roadTransportId: $this->id(),
                name: $name,
                unit: $unit,
                vehicle: $vehicle,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        Vehicle $vehicle,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new RoadTransportUpdated(
                roadTransportId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
                vehicle: $vehicle,
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
            new RoadTransportTranslated(
                roadTransportId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new RoadTransportRemoved(
                roadTransportId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new RoadTransportEmissionFactorAdded(
                    roadTransportId: $this->id(),
                    emissionFactorId: RoadTransportEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new RoadTransportEmissionFactorUpdated(
                roadTransportId: $this->id(),
                emissionFactor: $emissionFactor,
            ),
        );
    }

    public function removeEmissionFactor(int $year, EmissionFactorGroupId $groupId): void
    {
        if (!$this->emissionFactors()->hasForYearAndGroup($year, $groupId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new RoadTransportEmissionFactorRemoved(
                roadTransportId: $this->id(),
                year: $year,
                emissionFactorGroupId: $groupId,
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
        $newKeys = [];

        foreach ($emissionFactors as $emissionFactor) {
            $key = $emissionFactor->year() . '|' . $emissionFactor->emissionFactorGroupId();

            if (in_array($key, $newKeys, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate emission factor for year %d', $emissionFactor->year()));
            }

            $newKeys[] = $key;
            $this->setEmissionFactor($emissionFactor);
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
     * the road transport's own unit when the latter changes.
     */
    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $roadTransportEmissionFactor) {
            $emissionFactor = $roadTransportEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyRoadTransportRegistered(RoadTransportRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->vehicle = $event->vehicle();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyRoadTransportUpdated(RoadTransportUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->vehicle = $event->vehicle();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyRoadTransportTranslated(RoadTransportTranslated $event): void
    {
        $roadTransportTranslation = new RoadTransportTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($roadTransportTranslation);
    }

    public function applyRoadTransportRemoved(RoadTransportRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyRoadTransportEmissionFactorAdded(RoadTransportEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new RoadTransportEmissionFactor(
                id: $event->emissionFactorId(),
                roadTransport: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyRoadTransportEmissionFactorUpdated(RoadTransportEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyRoadTransportEmissionFactorRemoved(RoadTransportEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
