<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class GdOFactor implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<GdOFactorTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<GdOFactorEmissionFactor>|Collection<int, GdOFactorEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private GdOFactorId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->emissionFactors = [];
        $this->events = $events;
    }

    public function id(): GdOFactorId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var GdOFactorTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
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

    public function emissionFactors(): GdOFactorEmissionFactors
    {
        return new GdOFactorEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->emissionFactor();
    }

    public function wellToTankFactorFor(int $year, EmissionFactorGroupId $groupId): ?EmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId)?->wellToTankFactor();
    }

    public function register(string $name, ?Locale $locale = null): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorRegistered(
                gdOFactorId: $this->id(),
                name: $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(string $name, ?Locale $locale = null): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorUpdated(
                gdOFactorId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorTranslated(
                gdOFactorId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new GdOFactorRemoved(
                gdOFactorId: $this->id(),
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
                new GdOFactorEmissionFactorAdded(
                    gdOFactorId: $this->id(),
                    emissionFactorId: GdOFactorEmissionFactorId::generate(),
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
            new GdOFactorEmissionFactorUpdated(
                gdOFactorId: $this->id(),
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
            new GdOFactorEmissionFactorRemoved(
                gdOFactorId: $this->id(),
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

    public function applyGdOFactorRegistered(GdOFactorRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyGdOFactorUpdated(GdOFactorUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyGdOFactorTranslated(GdOFactorTranslated $event): void
    {
        $gdOFactorTranslation = new GdOFactorTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($gdOFactorTranslation);
    }

    public function applyGdOFactorRemoved(GdOFactorRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyGdOFactorEmissionFactorAdded(GdOFactorEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new GdOFactorEmissionFactor(
                id: $event->emissionFactorId(),
                gdOFactor: $this,
                emissionFactor: $event->emissionFactor()->withUnit(Unit::KWh),
                wellToTankFactor: $event->wellToTankFactor()->withUnit(Unit::KWh),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyGdOFactorEmissionFactorUpdated(GdOFactorEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor()->withUnit(Unit::KWh), $event->wellToTankFactor()->withUnit(Unit::KWh), $event->occurredOn());
    }

    public function applyGdOFactorEmissionFactorRemoved(GdOFactorEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
