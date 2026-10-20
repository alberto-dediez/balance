<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class ElectricityMix
{
    use AggregateRoot;

    /** @var iterable<ElectricityMixEmissionFactor>|Collection<int, ElectricityMixEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private ElectricityMixId $id,
        private ?CountryId $countryId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->emissionFactors = [];
        $this->events = $events;
    }

    public function id(): ElectricityMixId
    {
        return $this->id;
    }

    public function countryId(): CountryId
    {
        if (null === $this->countryId) {
            throw new \LogicException('Electricity mix has no country yet.');
        }

        return $this->countryId;
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

    public function emissionFactors(): ElectricityMixEmissionFactors
    {
        return new ElectricityMixEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function wellToTankFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->wellToTankFactor();
    }

    public function register(CountryId $countryId): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMixRegistered(
                electricityMixId: $this->id(),
                countryId: $countryId,
            ),
        );
    }

    public function update(CountryId $countryId): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMixUpdated(
                electricityMixId: $this->id(),
                countryId: $countryId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMixRemoved(
                electricityMixId: $this->id(),
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
                new ElectricityMixEmissionFactorAdded(
                    electricityMixId: $this->id(),
                    emissionFactorId: ElectricityMixEmissionFactorId::generate(),
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
            new ElectricityMixEmissionFactorUpdated(
                electricityMixId: $this->id(),
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
            new ElectricityMixEmissionFactorRemoved(
                electricityMixId: $this->id(),
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

    public function applyElectricityMixRegistered(ElectricityMixRegistered $event): void
    {
        $this->countryId = $event->countryId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyElectricityMixUpdated(ElectricityMixUpdated $event): void
    {
        $this->countryId = $event->countryId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyElectricityMixRemoved(ElectricityMixRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyElectricityMixEmissionFactorAdded(ElectricityMixEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new ElectricityMixEmissionFactor(
                id: $event->emissionFactorId(),
                electricityMix: $this,
                emissionFactor: $event->emissionFactor()->withUnit(Unit::KWh),
                wellToTankFactor: $event->wellToTankFactor()->withUnit(Unit::KWh),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyElectricityMixEmissionFactorUpdated(ElectricityMixEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor()->withUnit(Unit::KWh), $event->wellToTankFactor()->withUnit(Unit::KWh), $event->occurredOn());
    }

    public function applyElectricityMixEmissionFactorRemoved(ElectricityMixEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }
}
