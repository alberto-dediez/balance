<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class MaterialityQuestion implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialityQuestionTranslation>|Translations */
    private iterable $translations;

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

    public function register(
        MaterialityCriterion $criterion,
        ?MaterialityTopicId $topicId,
        string $text,
        string $help,
        MaterialityAnswerScale $answerScale,
        int $weight,
        int $position,
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

        if ($locale instanceof Locale) {
            $this->translate($text, $help, $locale);
        }
    }

    public function update(
        MaterialityCriterion $criterion,
        ?MaterialityTopicId $topicId,
        string $text,
        string $help,
        MaterialityAnswerScale $answerScale,
        int $weight,
        int $position,
        bool $active,
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
