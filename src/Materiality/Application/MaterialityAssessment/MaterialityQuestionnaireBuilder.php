<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityQuestionSnapshot;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityTopicSnapshot;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;

final class MaterialityQuestionnaireBuilder
{
    public function __construct(
        private MaterialityTopicRepository $topicRepository,
        private MaterialityQuestionRepository $questionRepository,
    ) {}

    public function build(?MaterialitySectorId $sectorId): MaterialityQuestionnaire
    {
        $topics = $this->topicRepository->findActive();

        usort(
            $topics,
            static fn(MaterialityTopic $a, MaterialityTopic $b) => [$a->scope()->value, $a->position(), $a->defaultName()] <=> [$b->scope()->value, $b->position(), $b->defaultName()],
        );

        $applicable = $this->questionRepository->findApplicableToSector($sectorId);
        $generic = array_values(array_filter($applicable, static fn(MaterialityQuestion $question) => $question->isGeneric()));

        $questions = [];

        foreach ($topics as $topic) {
            $specific = array_filter(
                $applicable,
                static fn(MaterialityQuestion $question) => $question->topicId()?->equals($topic->id()) ?? false,
            );

            $topicQuestions = [...$generic, ...array_values($specific)];

            usort(
                $topicQuestions,
                static fn(MaterialityQuestion $a, MaterialityQuestion $b) => [$a->position(), $a->registeredAt(), (string) $a->id()] <=> [$b->position(), $b->registeredAt(), (string) $b->id()],
            );

            foreach ($topicQuestions as $question) {
                $questions[] = MaterialityQuestionSnapshot::fromQuestion($question, $topic->id());
            }
        }

        return new MaterialityQuestionnaire(
            topics: array_map(static fn(MaterialityTopic $topic) => MaterialityTopicSnapshot::fromTopic($topic), $topics),
            questions: $questions,
        );
    }
}
