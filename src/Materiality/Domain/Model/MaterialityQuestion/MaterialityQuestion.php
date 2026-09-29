<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class MaterialityQuestion implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialityQuestionTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<MaterialityQuestionSectorLink>|Collection<int, MaterialityQuestionSectorLink> */
    private iterable $sectors;

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialityQuestionId $id,
        private MaterialityCriterion $criterion = MaterialityCriterion::Magnitude,
        private ?MaterialityTopicId $topicId = null,
        private string $text = '',
        private string $help = '',
        private MaterialityAnswerScale $answerScale = MaterialityAnswerScale::Level,
        private int $weight = 1,
        private int $position = 0,
        private bool $active = true,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->sectors = [];
        $this->events = $events;
    }

    public function id(): MaterialityQuestionId
    {
        return $this->id;
    }

    public function criterion(): MaterialityCriterion
    {
        return $this->criterion;
    }

    public function topicId(): ?MaterialityTopicId
    {
        return $this->topicId;
    }

    public function isGeneric(): bool
    {
        return null === $this->topicId;
    }

    public function sectors(): MaterialityQuestionSectors
    {
        return new MaterialityQuestionSectors($this->sectors);
    }

    /** @return list<MaterialitySectorId> */
    public function sectorIds(): array
    {
        return $this->sectors()->ids();
    }

    public function appliesToAllSectors(): bool
    {
        return 0 === count($this->sectors());
    }

    public function text(): string
    {
        /** @var MaterialityQuestionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->text() : $this->text;
    }

    public function help(): string
    {
        /** @var MaterialityQuestionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->help() : $this->help;
    }

    public function defaultText(): string
    {
        return $this->text;
    }

    public function defaultHelp(): string
    {
        return $this->help;
    }

    public function answerScale(): MaterialityAnswerScale
    {
        return $this->answerScale;
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function isActive(): bool
    {
        return $this->active;
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

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    /** @param list<MaterialitySectorId> $sectorIds */
    public function register(
        MaterialityCriterion $criterion,
        ?MaterialityTopicId $topicId,
        string $text,
        string $help,
        MaterialityAnswerScale $answerScale,
        int $weight,
        int $position,
        array $sectorIds = [],
        ?Locale $locale = null,
    ): void {
        $this->guardWeight($weight);

        $this->recordApplyAndPublish(
            new MaterialityQuestionRegistered(
                materialityQuestionId: $this->id(),
                criterion: $criterion,
                topicId: $topicId,
                text: $text,
                help: $help,
                answerScale: $answerScale,
                weight: $weight,
                position: $position,
            ),
        );

        $this->syncSectors($sectorIds);

        if ($locale instanceof Locale) {
            $this->translate($text, $help, $locale);
        }
    }

    /** @param list<MaterialitySectorId> $sectorIds */
    public function update(
        MaterialityCriterion $criterion,
        ?MaterialityTopicId $topicId,
        string $text,
        string $help,
        MaterialityAnswerScale $answerScale,
        int $weight,
        int $position,
        bool $active,
        array $sectorIds = [],
        ?Locale $locale = null,
    ): void {
        $this->guardWeight($weight);

        $this->recordApplyAndPublish(
            new MaterialityQuestionUpdated(
                materialityQuestionId: $this->id(),
                criterion: $criterion,
                topicId: $topicId,
                text: $locale instanceof Locale ? $this->text : $text,
                help: $locale instanceof Locale ? $this->help : $help,
                answerScale: $answerScale,
                weight: $weight,
                position: $position,
                active: $active,
            ),
        );

        $this->syncSectors($sectorIds);

        if ($locale instanceof Locale) {
            $this->translate($text, $help, $locale);
        }
    }

    public function translate(string $text, string $help, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new MaterialityQuestionTranslated(
                materialityQuestionId: $this->id(),
                text: $text,
                help: $help,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialityQuestionRemoved(
                materialityQuestionId: $this->id(),
            ),
        );
    }

    public function linkSector(MaterialitySectorId $sectorId): void
    {
        if ($this->sectors()->has($sectorId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialityQuestionSectorLinked(
                materialityQuestionId: $this->id(),
                linkId: MaterialityQuestionSectorLinkId::generate(),
                sectorId: $sectorId,
            ),
        );
    }

    public function unlinkSector(MaterialitySectorId $sectorId): void
    {
        if (!$this->sectors()->has($sectorId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialityQuestionSectorUnlinked(
                materialityQuestionId: $this->id(),
                sectorId: $sectorId,
            ),
        );
    }

    /** @param list<MaterialitySectorId> $sectorIds */
    private function syncSectors(array $sectorIds): void
    {
        foreach ($this->sectorIds() as $currentSectorId) {
            $kept = array_filter($sectorIds, static fn(MaterialitySectorId $sectorId) => $sectorId->equals($currentSectorId));

            if (0 === count($kept)) {
                $this->unlinkSector($currentSectorId);
            }
        }

        foreach ($sectorIds as $sectorId) {
            $this->linkSector($sectorId);
        }
    }

    private function guardWeight(int $weight): void
    {
        if ($weight < 1) {
            throw new InvalidMaterialityQuestionWeightException($weight);
        }
    }

    public function applyMaterialityQuestionRegistered(MaterialityQuestionRegistered $event): void
    {
        $this->criterion = $event->criterion();
        $this->topicId = $event->topicId();
        $this->text = $event->text();
        $this->help = $event->help();
        $this->answerScale = $event->answerScale();
        $this->weight = $event->weight();
        $this->position = $event->position();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialityQuestionUpdated(MaterialityQuestionUpdated $event): void
    {
        $this->criterion = $event->criterion();
        $this->topicId = $event->topicId();
        $this->text = $event->text();
        $this->help = $event->help();
        $this->answerScale = $event->answerScale();
        $this->weight = $event->weight();
        $this->position = $event->position();
        $this->active = $event->active();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialityQuestionTranslated(MaterialityQuestionTranslated $event): void
    {
        $this->translations()->add(
            new MaterialityQuestionTranslation(
                text: $event->text(),
                help: $event->help(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyMaterialityQuestionSectorLinked(MaterialityQuestionSectorLinked $event): void
    {
        $this->sectors()->add(
            new MaterialityQuestionSectorLink(
                id: $event->linkId(),
                materialityQuestion: $this,
                sectorId: $event->sectorId(),
            ),
        );
    }

    public function applyMaterialityQuestionSectorUnlinked(MaterialityQuestionSectorUnlinked $event): void
    {
        $this->sectors()->removeById($event->sectorId());
    }

    public function applyMaterialityQuestionRemoved(MaterialityQuestionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->text();
    }
}
