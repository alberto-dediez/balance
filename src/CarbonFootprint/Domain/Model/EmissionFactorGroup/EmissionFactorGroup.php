<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class EmissionFactorGroup implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<EmissionFactorGroupTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private EmissionFactorGroupId $id,
        private string $name = '',
        private Gwp $gwp = new Gwp(),
        private bool $removed = false,
        private bool $default = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): EmissionFactorGroupId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var EmissionFactorGroupTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function gwp(): Gwp
    {
        return $this->gwp;
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

    public function isDefault(): bool
    {
        return $this->default;
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

    public function register(
        string $name,
        Gwp $gwp,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupRegistered(
                emissionFactorGroupId: $this->id(),
                name: $name,
                gwp: $gwp,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        Gwp $gwp,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupUpdated(
                emissionFactorGroupId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                gwp: $gwp,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupTranslated(
                emissionFactorGroupId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupRemoved(
                emissionFactorGroupId: $this->id(),
            ),
        );
    }

    public function setAsDefault(): void
    {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupSetAsDefault(
                emissionFactorGroupId: $this->id(),
            ),
        );
    }

    public function unsetAsDefault(): void
    {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupUnsetAsDefault(
                emissionFactorGroupId: $this->id(),
            ),
        );
    }

    public function co2eqFor(string $co2, string $ch4, string $n2o): string
    {
        return $this->gwp->co2eqFor($co2, $ch4, $n2o);
    }

    public function buildEmissionFactor(string $co2eq, string $co2, string $ch4, string $n2o, int $year, Unit $unit, ?string $source): EmissionFactor
    {
        if (0.0 === (float) $co2eq) {
            $co2eq = $this->co2eqFor($co2, $ch4, $n2o);
        }

        return new EmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $this->id, $unit, $source);
    }

    public function applyEmissionFactorGroupRegistered(EmissionFactorGroupRegistered $event): void
    {
        $this->name = $event->name();
        $this->gwp = $event->gwp();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEmissionFactorGroupUpdated(EmissionFactorGroupUpdated $event): void
    {
        $this->name = $event->name();
        $this->gwp = $event->gwp();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmissionFactorGroupTranslated(EmissionFactorGroupTranslated $event): void
    {
        $emissionFactorGroupTranslation = new EmissionFactorGroupTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($emissionFactorGroupTranslation);
    }

    public function applyEmissionFactorGroupRemoved(EmissionFactorGroupRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyEmissionFactorGroupSetAsDefault(EmissionFactorGroupSetAsDefault $event): void
    {
        $this->default = true;
    }

    public function applyEmissionFactorGroupUnsetAsDefault(EmissionFactorGroupUnsetAsDefault $event): void
    {
        $this->default = false;
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
