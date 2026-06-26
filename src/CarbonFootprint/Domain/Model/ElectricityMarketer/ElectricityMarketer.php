<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
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

    public function emissionFactorFor(int $year): ?EmissionFactor
    {
        return $this->emissionFactors()->forYear($year)?->emissionFactor();
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

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYear($emissionFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new ElectricityMarketerEmissionFactorAdded(
                    electricityMarketerId: $this->id(),
                    emissionFactorId: ElectricityMarketerEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ElectricityMarketerEmissionFactorUpdated(
                electricityMarketerId: $this->id(),
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
            new ElectricityMarketerEmissionFactorRemoved(
                electricityMarketerId: $this->id(),
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
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyElectricityMarketerEmissionFactorUpdated(ElectricityMarketerEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->emissionFactor()->year())
            ?->update($event->emissionFactor()->withUnit(Unit::KWh), $event->occurredOn());
    }

    public function applyElectricityMarketerEmissionFactorRemoved(ElectricityMarketerEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
