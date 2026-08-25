<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class Mobility implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MobilityTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MobilityEmissionFactor>|Collection<int, MobilityEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MobilityId $id,
        private string $name = '',
        private Unit $unit = Unit::Kilometer,
        private ?TravelMode $travelMode = null,
        private FactorBasis $factorBasis = FactorBasis::PerPassenger,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MobilityId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var MobilityTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function travelMode(): ?TravelMode
    {
        return $this->travelMode;
    }

    public function factorBasis(): FactorBasis
    {
        return $this->factorBasis;
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

    public function emissionFactors(): MobilityEmissionFactors
    {
        return new MobilityEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function register(
        string $name,
        Unit $unit,
        ?TravelMode $travelMode = null,
        FactorBasis $factorBasis = FactorBasis::PerPassenger,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MobilityRegistered(
                mobilityId: $this->id(),
                name: $name,
                unit: $unit,
                travelMode: $travelMode,
                factorBasis: $factorBasis,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        ?TravelMode $travelMode = null,
        FactorBasis $factorBasis = FactorBasis::PerPassenger,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new MobilityUpdated(
                mobilityId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
                travelMode: $travelMode,
                factorBasis: $factorBasis,
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
            new MobilityTranslated(
                mobilityId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MobilityRemoved(
                mobilityId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new MobilityEmissionFactorAdded(
                    mobilityId: $this->id(),
                    emissionFactorId: MobilityEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MobilityEmissionFactorUpdated(
                mobilityId: $this->id(),
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
            new MobilityEmissionFactorRemoved(
                mobilityId: $this->id(),
                year: $year,
                emissionFactorGroupId: $groupId,
            ),
        );
    }

    /** @param EmissionFactor[] $emissionFactors */
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

    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $mobilityEmissionFactor) {
            $emissionFactor = $mobilityEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyMobilityRegistered(MobilityRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->travelMode = $event->travelMode();
        $this->factorBasis = $event->factorBasis();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMobilityUpdated(MobilityUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->travelMode = $event->travelMode();
        $this->factorBasis = $event->factorBasis();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMobilityTranslated(MobilityTranslated $event): void
    {
        $mobilityTranslation = new MobilityTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($mobilityTranslation);
    }

    public function applyMobilityRemoved(MobilityRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMobilityEmissionFactorAdded(MobilityEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new MobilityEmissionFactor(
                id: $event->emissionFactorId(),
                mobility: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMobilityEmissionFactorUpdated(MobilityEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyMobilityEmissionFactorRemoved(MobilityEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
