<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class PurchaseService implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<PurchaseServiceTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<PurchaseServiceEmissionFactor>|Collection<int, PurchaseServiceEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private PurchaseServiceId $id,
        private string $name = '',
        private PurchaseServiceCategory $category = PurchaseServiceCategory::Others,
        private Unit $unit = Unit::Euro,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): PurchaseServiceId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var PurchaseServiceTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function category(): PurchaseServiceCategory
    {
        return $this->category;
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

    public function emissionFactors(): PurchaseServiceEmissionFactors
    {
        return new PurchaseServiceEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year, EmissionFactorGroupId $groupId): ?PurchaseServiceEmissionFactor
    {
        return $this->emissionFactors()->forYearAndGroup($year, $groupId);
    }

    public function register(
        string $name,
        PurchaseServiceCategory $category,
        Unit $unit,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new PurchaseServiceRegistered(
                purchaseServiceId: $this->id(),
                name: $name,
                category: $category,
                unit: $unit,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        PurchaseServiceCategory $category,
        Unit $unit,
        ?Locale $locale = null,
    ): void {
        $previousUnit = $this->unit;

        $this->recordApplyAndPublish(
            new PurchaseServiceUpdated(
                purchaseServiceId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                category: $category,
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
            new PurchaseServiceTranslated(
                purchaseServiceId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PurchaseServiceRemoved(
                purchaseServiceId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new PurchaseServiceEmissionFactorAdded(
                    purchaseServiceId: $this->id(),
                    emissionFactorId: PurchaseServiceEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new PurchaseServiceEmissionFactorUpdated(
                purchaseServiceId: $this->id(),
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
            new PurchaseServiceEmissionFactorRemoved(
                purchaseServiceId: $this->id(),
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

    private function restampEmissionFactorUnits(Unit $unit): void
    {
        foreach ($this->emissionFactors() as $purchaseServiceEmissionFactor) {
            $emissionFactor = $purchaseServiceEmissionFactor->emissionFactor();

            if ($emissionFactor->unit() === $unit) {
                continue;
            }

            $this->setEmissionFactor($emissionFactor->withUnit($unit));
        }
    }

    public function applyPurchaseServiceRegistered(PurchaseServiceRegistered $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPurchaseServiceUpdated(PurchaseServiceUpdated $event): void
    {
        $this->name = $event->name();
        $this->category = $event->category();
        $this->unit = $event->unit();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPurchaseServiceTranslated(PurchaseServiceTranslated $event): void
    {
        $purchaseServiceTranslation = new PurchaseServiceTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($purchaseServiceTranslation);
    }

    public function applyPurchaseServiceRemoved(PurchaseServiceRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyPurchaseServiceEmissionFactorAdded(PurchaseServiceEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new PurchaseServiceEmissionFactor(
                id: $event->emissionFactorId(),
                purchaseService: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyPurchaseServiceEmissionFactorUpdated(PurchaseServiceEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYearAndGroup($event->emissionFactor()->year(), $event->emissionFactor()->emissionFactorGroupId())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyPurchaseServiceEmissionFactorRemoved(PurchaseServiceEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYearAndGroup($event->year(), $event->emissionFactorGroupId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
