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
        private string $code = '',
        private string $name = '',
        private Gwp $gwp = new Gwp(),
        private bool $removed = false,
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

    public function code(): string
    {
        return $this->code;
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
        string $code,
        string $name,
        Gwp $gwp,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupRegistered(
                emissionFactorGroupId: $this->id(),
                code: $code,
                name: $name,
                gwp: $gwp,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $code,
        string $name,
        Gwp $gwp,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new EmissionFactorGroupUpdated(
                emissionFactorGroupId: $this->id(),
                code: $code,
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

    public function co2eqFor(string $co2, string $ch4, string $n2o): string
    {
        return (string) ((float) $co2 * $this->gwp->gwpCo2()
            + (float) $ch4 * $this->gwp->gwpCh4()
            + (float) $n2o * $this->gwp->gwpN2O());
    }

    public function buildEmissionFactor(string $co2, string $ch4, string $n2o, int $year, Unit $unit, ?string $source): EmissionFactor
    {
        $co2eq = $this->co2eqFor($co2, $ch4, $n2o);

        return new EmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
    }

    public function applyEmissionFactorGroupRegistered(EmissionFactorGroupRegistered $event): void
    {
        $this->code = $event->code();
        $this->name = $event->name();
        $this->gwp = $event->gwp();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEmissionFactorGroupUpdated(EmissionFactorGroupUpdated $event): void
    {
        $this->code = $event->code();
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

    public function __toString(): string
    {
        return $this->name();
    }
}
