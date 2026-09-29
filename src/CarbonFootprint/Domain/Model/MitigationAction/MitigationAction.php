<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class MitigationAction implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MitigationActionTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MitigationActionEffect>|Collection<int, MitigationActionEffect> */
    private iterable $effects = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MitigationActionId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private ?MitigationMeasureId $measureId = null,
        private string $name = '',
        private string $description = '',
        private int $baseYear = 0,
        private int $targetYear = 0,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MitigationActionId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function measureId(): ?MitigationMeasureId
    {
        return $this->measureId;
    }

    public function isManual(): bool
    {
        return null === $this->measureId;
    }

    public function name(): string
    {
        /** @var MitigationActionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function description(): string
    {
        /** @var MitigationActionTranslation|null $translation */
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

    public function baseYear(): int
    {
        return $this->baseYear;
    }

    public function targetYear(): int
    {
        return $this->targetYear;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function effects(): MitigationActionEffects
    {
        return new MitigationActionEffects($this->effects);
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
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        AccountId $accountId,
        ?MitigationMeasureId $measureId,
        string $name,
        string $description,
        int $baseYear,
        int $targetYear,
        ?Locale $locale = null,
    ): void {
        self::assertValidYears($baseYear, $targetYear);

        $this->recordApplyAndPublish(
            new MitigationActionRegistered(
                mitigationActionId: $this->id(),
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
                measureId: $measureId,
                name: $name,
                description: $description,
                baseYear: $baseYear,
                targetYear: $targetYear,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function update(string $name, string $description, int $targetYear, ?Locale $locale = null): void
    {
        self::assertValidYears($this->baseYear, $targetYear);

        $this->recordApplyAndPublish(
            new MitigationActionUpdated(
                mitigationActionId: $this->id(),
                name: $locale instanceof Locale ? $this->name : $name,
                description: $locale instanceof Locale ? $this->description : $description,
                targetYear: $targetYear,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function translate(string $name, string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new MitigationActionTranslated(
                mitigationActionId: $this->id(),
                name: $name,
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MitigationActionRemoved(
                mitigationActionId: $this->id(),
            ),
        );
    }

    public function setEffect(
        Source $source,
        string $itemId,
        string $itemName,
        float $reduction,
        float $minimumReduction = 0.0,
        float $maximumReduction = 1.0,
    ): void {
        self::assertSupportedSource($source);
        self::assertReductionInRange($reduction, $minimumReduction, $maximumReduction);

        $current = $this->effects()->forSourceAndItem($source, $itemId);

        if (null === $current) {
            $this->recordApplyAndPublish(
                new MitigationActionEffectAdded(
                    mitigationActionId: $this->id(),
                    effectId: MitigationActionEffectId::generate(),
                    source: $source,
                    itemId: $itemId,
                    itemName: $itemName,
                    reduction: $reduction,
                    minimumReduction: $minimumReduction,
                    maximumReduction: $maximumReduction,
                ),
            );

            return;
        }

        if (
            $current->reduction() === $reduction
            && $current->minimumReduction() === $minimumReduction
            && $current->maximumReduction() === $maximumReduction
        ) {
            return;
        }

        $this->recordApplyAndPublish(
            new MitigationActionEffectUpdated(
                mitigationActionId: $this->id(),
                source: $source,
                itemId: $itemId,
                reduction: $reduction,
                minimumReduction: $minimumReduction,
                maximumReduction: $maximumReduction,
            ),
        );
    }

    public function changeReduction(Source $source, string $itemId, float $reduction): void
    {
        $current = $this->effects()->forSourceAndItem($source, $itemId);

        if (null === $current) {
            return;
        }

        $this->setEffect(
            $source,
            $itemId,
            $current->itemName(),
            $reduction,
            $current->minimumReduction(),
            $current->maximumReduction(),
        );
    }

    public function removeEffect(Source $source, string $itemId): void
    {
        if (!$this->effects()->hasForSourceAndItem($source, $itemId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MitigationActionEffectRemoved(
                mitigationActionId: $this->id(),
                source: $source,
                itemId: $itemId,
            ),
        );
    }

    /** @param list<array{source: Source, itemId: string, itemName: string, reduction: float}> $effects */
    public function syncEffects(array $effects): void
    {
        $newKeys = [];

        foreach ($effects as $effect) {
            $key = $effect['source']->value . '|' . $effect['itemId'];

            if (in_array($key, $newKeys, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate mitigation action effect for %s', $key));
            }

            $newKeys[] = $key;
            $this->setEffect($effect['source'], $effect['itemId'], $effect['itemName'], $effect['reduction']);
        }

        foreach ($this->effects() as $existingEffect) {
            $key = $existingEffect->source()->value . '|' . $existingEffect->itemId();

            if (!in_array($key, $newKeys, true)) {
                $this->removeEffect($existingEffect->source(), $existingEffect->itemId());
            }
        }
    }

    private static function assertValidYears(int $baseYear, int $targetYear): void
    {
        if ($targetYear <= $baseYear) {
            throw new MitigationActionInvalidYearsException(sprintf('Target year %d must be after base year %d', $targetYear, $baseYear));
        }
    }

    private static function assertSupportedSource(Source $source): void
    {
        if (null === Scope::ofSource($source)) {
            throw new MitigationActionSourceNotSupportedException(sprintf('Source %s is not supported by mitigation actions', $source->value));
        }
    }

    private static function assertReductionInRange(float $reduction, float $minimumReduction, float $maximumReduction): void
    {
        if (
            $minimumReduction < 0.0
            || $minimumReduction > $reduction
            || $reduction > $maximumReduction
            || $maximumReduction > 1.0
        ) {
            throw new MitigationActionReductionOutOfRangeException(sprintf('Reduction %s must be between %s and %s', $reduction, $minimumReduction, $maximumReduction));
        }
    }

    public function applyMitigationActionRegistered(MitigationActionRegistered $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->measureId = $event->measureId();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->baseYear = $event->baseYear();
        $this->targetYear = $event->targetYear();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMitigationActionUpdated(MitigationActionUpdated $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->targetYear = $event->targetYear();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMitigationActionTranslated(MitigationActionTranslated $event): void
    {
        $this->translations()->add(
            new MitigationActionTranslation(
                name: $event->name(),
                description: $event->description(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyMitigationActionRemoved(MitigationActionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyMitigationActionEffectAdded(MitigationActionEffectAdded $event): void
    {
        $this->effects()->add(
            new MitigationActionEffect(
                id: $event->effectId(),
                action: $this,
                source: $event->source(),
                itemId: $event->itemId(),
                itemName: $event->itemName(),
                reduction: $event->reduction(),
                minimumReduction: $event->minimumReduction(),
                maximumReduction: $event->maximumReduction(),
                createdAt: $event->occurredOn(),
            ),
        );
    }

    public function applyMitigationActionEffectUpdated(MitigationActionEffectUpdated $event): void
    {
        $this->effects()
            ->forSourceAndItem($event->source(), $event->itemId())
            ?->update($event->reduction(), $event->minimumReduction(), $event->maximumReduction(), $event->occurredOn());
    }

    public function applyMitigationActionEffectRemoved(MitigationActionEffectRemoved $event): void
    {
        $this->effects()->removeForSourceAndItem($event->source(), $event->itemId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
