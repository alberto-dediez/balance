<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

final readonly class MaterialityTopicSnapshot
{
    public function __construct(
        public MaterialityTopicId $topicId,
        public ?int $number,
        public MaterialityScope $scope,
        public ?MaterialityTopicDirection $direction,
        public string $name,
        public string $description,
        public int $position,
        public ?MaterialityAssessmentTopicId $assessmentTopicId = null,
    ) {}

    public static function fromTopic(MaterialityTopic $topic): self
    {
        return new self(
            topicId: $topic->id(),
            number: $topic->number(),
            scope: $topic->scope(),
            direction: $topic->direction(),
            name: $topic->defaultName(),
            description: $topic->defaultDescription(),
            position: $topic->position(),
        );
    }

    public function withAssessmentTopicId(MaterialityAssessmentTopicId $assessmentTopicId): self
    {
        return new self(
            topicId: $this->topicId,
            number: $this->number,
            scope: $this->scope,
            direction: $this->direction,
            name: $this->name,
            description: $this->description,
            position: $this->position,
            assessmentTopicId: $assessmentTopicId,
        );
    }

    /** @return array<string, mixed> */
    public function toPrimitives(): array
    {
        return [
            'topicId' => (string) $this->topicId,
            'number' => $this->number,
            'scope' => $this->scope->value,
            'direction' => $this->direction?->value,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'assessmentTopicId' => null !== $this->assessmentTopicId ? (string) $this->assessmentTopicId : null,
        ];
    }

    /** @param array<string, mixed> $primitives */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            topicId: new MaterialityTopicId($primitives['topicId']),
            number: $primitives['number'],
            scope: MaterialityScope::from($primitives['scope']),
            direction: null !== $primitives['direction'] ? MaterialityTopicDirection::from($primitives['direction']) : null,
            name: $primitives['name'],
            description: $primitives['description'],
            position: $primitives['position'],
            assessmentTopicId: null !== ($primitives['assessmentTopicId'] ?? null) ? new MaterialityAssessmentTopicId($primitives['assessmentTopicId']) : null,
        );
    }
}
