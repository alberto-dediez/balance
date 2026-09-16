<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class RefrigerantGas implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<RefrigerantGasEmissionFactor>|Collection<int, RefrigerantGasEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private RefrigerantGasId $id,
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

    public function id(): RefrigerantGasId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return Unit::Kilogram;
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

    public function emissionFactors(): RefrigerantGasEmissionFactors
    {
        return new RefrigerantGasEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function register(string $name): void
    {
        $this->recordApplyAndPublish(
            new RefrigerantGasRegistered(
                refrigerantGasId: $this->id(),
                name: $name,
            ),
        );
    }

    public function update(string $name): void
    {
        $this->recordApplyAndPublish(
            new RefrigerantGasUpdated(
                refrigerantGasId: $this->id(),
                name: $name,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new RefrigerantGasRemoved(
                refrigerantGasId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new RefrigerantGasEmissionFactorAdded(
                    refrigerantGasId: $this->id(),
                    emissionFactorId: RefrigerantGasEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new RefrigerantGasEmissionFactorUpdated(
                refrigerantGasId: $this->id(),
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
            new RefrigerantGasEmissionFactorRemoved(
                refrigerantGasId: $this->id(),
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

    public function applyRefrigerantGasRegistered(RefrigerantGasRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyRefrigerantGasUpdated(RefrigerantGasUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyRefrigerantGasRemoved(RefrigerantGasRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyRefrigerantGasEmissionFactorAdded(RefrigerantGasEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new RefrigerantGasEmissionFactor(
                id: $event->emissionFactorId(),
                refrigerantGas: $this,
                emissionFactor: $event->emissionFactor()->withUnit(Unit::Kilogram),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyRefrigerantGasEmissionFactorUpdated(RefrigerantGasEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor()->withUnit(Unit::Kilogram), $event->occurredOn());
    }

    public function applyRefrigerantGasEmissionFactorRemoved(RefrigerantGasEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
