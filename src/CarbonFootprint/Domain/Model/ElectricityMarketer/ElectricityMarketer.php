<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class ElectricityMarketer implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<ElectricityMarketerEmissionFactor>|Collection<int, ElectricityMarketerEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private ElectricityMarketerId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->emissionFactors = [];
        $this->events = $events;
    }

    public function id(): ElectricityMarketerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
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

    public function emissionFactors(): ElectricityMarketerEmissionFactors
    {
        return new ElectricityMarketerEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function wellToTankFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->wellToTankFactor();
    }

    public function register(string $name): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMarketerRegistered(
                electricityMarketerId: $this->id(),
                name: $name,
            ),
        );
    }

    public function update(string $name): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMarketerUpdated(
                electricityMarketerId: $this->id(),
                name: $name,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ElectricityMarketerRemoved(
                electricityMarketerId: $this->id(),
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
                new ElectricityMarketerEmissionFactorAdded(
                    electricityMarketerId: $this->id(),
                    emissionFactorId: ElectricityMarketerEmissionFactorId::generate(),
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
            new ElectricityMarketerEmissionFactorUpdated(
                electricityMarketerId: $this->id(),
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
            new ElectricityMarketerEmissionFactorRemoved(
                electricityMarketerId: $this->id(),
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

    public function applyElectricityMarketerRegistered(ElectricityMarketerRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyElectricityMarketerUpdated(ElectricityMarketerUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyElectricityMarketerRemoved(ElectricityMarketerRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyElectricityMarketerEmissionFactorAdded(ElectricityMarketerEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new ElectricityMarketerEmissionFactor(
                id: $event->emissionFactorId(),
                electricityMarketer: $this,
                emissionFactor: $event->emissionFactor()->withUnit(Unit::KWh),
                wellToTankFactor: $event->wellToTankFactor()->withUnit(Unit::KWh),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyElectricityMarketerEmissionFactorUpdated(ElectricityMarketerEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor()->withUnit(Unit::KWh), $event->wellToTankFactor()->withUnit(Unit::KWh), $event->occurredOn());
    }

    public function applyElectricityMarketerEmissionFactorRemoved(ElectricityMarketerEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
