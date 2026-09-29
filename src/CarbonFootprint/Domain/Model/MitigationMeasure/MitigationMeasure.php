<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class MitigationMeasure implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MitigationMeasureTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MitigationMeasureEffect>|Collection<int, MitigationMeasureEffect> */
    private iterable $effects = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MitigationMeasureId $id,
        private ?MitigationMeasureCategoryId $categoryId = null,
        private string $name = '',
        private string $description = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MitigationMeasureId
    {
        return $this->id;
    }

    public function categoryId(): ?MitigationMeasureCategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        /** @var MitigationMeasureTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function description(): string
    {
        /** @var MitigationMeasureTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
    }

    public function defaultName(): string
    {
        return $this->name;
    }

    public function defaultDescription(): string
    {
        return $this->description;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function effects(): MitigationMeasureEffects
    {
        return new MitigationMeasureEffects($this->effects);
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
        string $name,
        string $description,
        MitigationMeasureCategoryId $categoryId,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MitigationMeasureRegistered(
                mitigationMeasureId: $this->id(),
                categoryId: $categoryId,
                name: $name,
                description: $description,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function update(
        string $name,
        string $description,
        MitigationMeasureCategoryId $categoryId,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MitigationMeasureUpdated(
                mitigationMeasureId: $this->id(),
                categoryId: $categoryId,
                name: $locale instanceof Locale ? $this->name : $name,
                description: $locale instanceof Locale ? $this->description : $description,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function translate(string $name, string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new MitigationMeasureTranslated(
                mitigationMeasureId: $this->id(),
                name: $name,
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MitigationMeasureRemoved(
                mitigationMeasureId: $this->id(),
            ),
        );
    }

    public function setEffect(Source $source, float $minimumReduction, float $maximumReduction): void
    {
        if (null === Scope::ofSource($source)) {
            throw new \InvalidArgumentException(sprintf('Source %s is not supported by mitigation measures', $source->value));
        }

        if ($minimumReduction < 0.0 || $minimumReduction > $maximumReduction || $maximumReduction > 1.0) {
            throw new \InvalidArgumentException(sprintf('Invalid reduction range %s - %s', $minimumReduction, $maximumReduction));
        }

        $current = $this->effects()->forSource($source);

        if (null === $current) {
            $this->recordApplyAndPublish(
                new MitigationMeasureEffectAdded(
                    mitigationMeasureId: $this->id(),
                    effectId: MitigationMeasureEffectId::generate(),
                    source: $source,
                    minimumReduction: $minimumReduction,
                    maximumReduction: $maximumReduction,
                ),
            );

            return;
        }

        if ($current->minimumReduction() === $minimumReduction && $current->maximumReduction() === $maximumReduction) {
            return;
        }

        $this->recordApplyAndPublish(
            new MitigationMeasureEffectUpdated(
                mitigationMeasureId: $this->id(),
                source: $source,
                minimumReduction: $minimumReduction,
                maximumReduction: $maximumReduction,
            ),
        );
    }

    public function removeEffect(Source $source): void
    {
        if (!$this->effects()->hasForSource($source)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MitigationMeasureEffectRemoved(
                mitigationMeasureId: $this->id(),
                source: $source,
            ),
        );
    }

    /** @param list<array{source: Source, minimumReduction: float, maximumReduction: float}> $effects */
    public function syncEffects(array $effects): void
    {
        $newSources = [];

        foreach ($effects as $effect) {
            if (in_array($effect['source'], $newSources, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate mitigation measure effect for %s', $effect['source']->value));
            }

            $newSources[] = $effect['source'];
            $this->setEffect($effect['source'], $effect['minimumReduction'], $effect['maximumReduction']);
        }

        foreach ($this->effects() as $existingEffect) {
            if (!in_array($existingEffect->source(), $newSources, true)) {
                $this->removeEffect($existingEffect->source());
            }
        }
    }

    public function applyMitigationMeasureRegistered(MitigationMeasureRegistered $event): void
    {
        $this->categoryId = $event->categoryId();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMitigationMeasureUpdated(MitigationMeasureUpdated $event): void
    {
        $this->categoryId = $event->categoryId();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMitigationMeasureTranslated(MitigationMeasureTranslated $event): void
    {
        $this->translations()->add(
            new MitigationMeasureTranslation(
                name: $event->name(),
                description: $event->description(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyMitigationMeasureRemoved(MitigationMeasureRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMitigationMeasureEffectAdded(MitigationMeasureEffectAdded $event): void
    {
        $this->effects()->add(
            new MitigationMeasureEffect(
                id: $event->effectId(),
                measure: $this,
                source: $event->source(),
                minimumReduction: $event->minimumReduction(),
                maximumReduction: $event->maximumReduction(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMitigationMeasureEffectUpdated(MitigationMeasureEffectUpdated $event): void
    {
        $this->effects()
            ->forSource($event->source())
            ?->update($event->minimumReduction(), $event->maximumReduction(), $event->occurredOn());
    }

    public function applyMitigationMeasureEffectRemoved(MitigationMeasureEffectRemoved $event): void
    {
        $this->effects()->removeForSource($event->source());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
