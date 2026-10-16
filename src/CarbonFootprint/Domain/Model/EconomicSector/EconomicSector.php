<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class EconomicSector implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<EconomicSectorTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<EconomicSectorEmissionFactor>|Collection<int, EconomicSectorEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private EconomicSectorId $id,
        private string $name = '',
        private Unit $unit = Unit::Euro,
        private ?string $classificationCode = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): EconomicSectorId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var EconomicSectorTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function classificationCode(): ?string
    {
        return $this->classificationCode;
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

    public function emissionFactors(): EconomicSectorEmissionFactors
    {
        return new EconomicSectorEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?EconomicSectorEmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId);
    }

    public function register(
        string $name,
        Unit $unit,
        ?string $classificationCode,
        ?Locale $locale = null,
    ): void {
        $this->assertCurrency($unit);

        $this->recordApplyAndPublish(
            new EconomicSectorRegistered(
                economicSectorId: $this->id(),
                name: $name,
                unit: $unit,
                classificationCode: $classificationCode,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Unit $unit,
        ?string $classificationCode,
        ?Locale $locale = null,
    ): void {
        $this->assertCurrency($unit);

        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new EconomicSectorUpdated(
                economicSectorId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                unit: $unit,
                classificationCode: $classificationCode,
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
            new EconomicSectorTranslated(
                economicSectorId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EconomicSectorRemoved(
                economicSectorId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new EconomicSectorEmissionFactorAdded(
                    economicSectorId: $this->id(),
                    emissionFactorId: EconomicSectorEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EconomicSectorEmissionFactorUpdated(
                economicSectorId: $this->id(),
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
            new EconomicSectorEmissionFactorRemoved(
                economicSectorId: $this->id(),
                year: $year,
                emissionFactorGroupId: $groupId,
            ),
        );
    }

    /** @param list<EmissionFactor> $emissionFactors */
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

    private function assertCurrency(Unit $unit): void
    {
        if (!$unit->isCurrency()) {
            throw new NonCurrencyUnitException($unit);
        }
    }

    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $economicSectorEmissionFactor) {
            $emissionFactor = $economicSectorEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyEconomicSectorRegistered(EconomicSectorRegistered $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->classificationCode = $event->classificationCode();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEconomicSectorUpdated(EconomicSectorUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->classificationCode = $event->classificationCode();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEconomicSectorTranslated(EconomicSectorTranslated $event): void
    {
        $economicSectorTranslation = new EconomicSectorTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($economicSectorTranslation);
    }

    public function applyEconomicSectorRemoved(EconomicSectorRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyEconomicSectorEmissionFactorAdded(EconomicSectorEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new EconomicSectorEmissionFactor(
                id: $event->emissionFactorId(),
                economicSector: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyEconomicSectorEmissionFactorUpdated(EconomicSectorEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyEconomicSectorEmissionFactorRemoved(EconomicSectorEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
