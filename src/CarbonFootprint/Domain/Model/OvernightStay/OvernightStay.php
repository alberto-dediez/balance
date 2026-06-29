<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class OvernightStay implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<OvernightStayTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<OvernightStayEmissionFactor>|Collection<int, OvernightStayEmissionFactor> */
    private iterable $emissionFactors = [];

    /** @param array<mixed> $events */
    public function __construct(
        private OvernightStayId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): OvernightStayId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var OvernightStayTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function unit(): Unit
    {
        return Unit::Pernoctacion;
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

    public function emissionFactors(): OvernightStayEmissionFactors
    {
        return new OvernightStayEmissionFactors($this->emissionFactors);
    }

    public function emissionFactorFor(int $year): ?EmissionFactor
    {
        return $this->emissionFactors()->forYear($year)?->emissionFactor();
    }

    public function register(
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new OvernightStayRegistered(
                overnightStayId: $this->id(),
                name: $name,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new OvernightStayUpdated(
                overnightStayId: $this->id(),
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
            new OvernightStayTranslated(
                overnightStayId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new OvernightStayRemoved(
                overnightStayId: $this->id(),
            ),
        );
    }

    public function setEmissionFactor(EmissionFactor $emissionFactor): void
    {
        $current = $this->emissionFactors()->forYear($emissionFactor->year());

        if (null === $current) {
            $this->recordApplyAndPublish(
                new OvernightStayEmissionFactorAdded(
                    overnightStayId: $this->id(),
                    emissionFactorId: OvernightStayEmissionFactorId::generate(),
                    emissionFactor: $emissionFactor,
                ),
            );

            return;
        }

        if ($current->emissionFactor()->equals($emissionFactor)) {
            return;
        }

        $this->recordApplyAndPublish(
            new OvernightStayEmissionFactorUpdated(
                overnightStayId: $this->id(),
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
            new OvernightStayEmissionFactorRemoved(
                overnightStayId: $this->id(),
                year: $year,
            ),
        );
    }

    /**
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

    public function applyOvernightStayRegistered(OvernightStayRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOvernightStayUpdated(OvernightStayUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOvernightStayTranslated(OvernightStayTranslated $event): void
    {
        $overnightStayTranslation = new OvernightStayTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($overnightStayTranslation);
    }

    public function applyOvernightStayRemoved(OvernightStayRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyOvernightStayEmissionFactorAdded(OvernightStayEmissionFactorAdded $event): void
    {
        $this->emissionFactors()->add(
            new OvernightStayEmissionFactor(
                id: $event->emissionFactorId(),
                overnightStay: $this,
                emissionFactor: $event->emissionFactor(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyOvernightStayEmissionFactorUpdated(OvernightStayEmissionFactorUpdated $event): void
    {
        $this->emissionFactors()
            ->forYear($event->emissionFactor()->year())
            ?->update($event->emissionFactor(), $event->occurredOn());
    }

    public function applyOvernightStayEmissionFactorRemoved(OvernightStayEmissionFactorRemoved $event): void
    {
        $this->emissionFactors()->removeForYear($event->year());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
