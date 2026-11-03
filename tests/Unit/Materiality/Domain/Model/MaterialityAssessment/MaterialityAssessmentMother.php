<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityQuestionSnapshot;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityTopicSnapshot;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;

class MaterialityAssessmentMother
{
    /**
     * @param list<MaterialityTopic>|null    $topics
     * @param list<MaterialityQuestion>|null $questions
     * @param array<string, int>             $initialValues
     */
    public static function draft(
        ?string $id = null,
        MaterialityAssessmentSubjectType $subjectType = MaterialityAssessmentSubjectType::Property,
        string $carbonFootprintId = 'footprint-id',
        string $accountId = 'account-id',
        int $year = 2025,
        ?MaterialitySectorId $sectorId = null,
        ?array $topics = null,
        ?array $questions = null,
        array $initialValues = [],
        ?string $sectorName = null,
    ): MaterialityAssessment {
        $topics ??= [MaterialityTopicMother::random()];
        $questions ??= [MaterialityQuestionMother::random()];

        $topicSnapshots = array_map(static fn(MaterialityTopic $topic) => MaterialityTopicSnapshot::fromTopic($topic), $topics);
        $questionSnapshots = [];

        foreach ($topics as $topic) {
            foreach ($questions as $question) {
                if ($question->isGeneric() || $question->topicId()?->equals($topic->id())) {
                    $questionSnapshots[] = MaterialityQuestionSnapshot::fromQuestion($question, $topic->id());
                }
            }
        }

        $materialityAssessment = new MaterialityAssessment(
            id: new MaterialityAssessmentId($id ?? (string) MaterialityAssessmentId::generate()),
        );
        $materialityAssessment->start(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($accountId),
            year: $year,
            sectorId: $sectorId,
            sectorName: $sectorName ?? (null !== $sectorId ? 'sector-name' : null),
            topics: $topicSnapshots,
            questions: $questionSnapshots,
            initialValues: $initialValues,
        );

        return $materialityAssessment;
    }

    /**
     * @param list<MaterialityTopic>|null    $topics
     * @param list<MaterialityQuestion>|null $questions
     */
    public static function answered(
        ?string $id = null,
        MaterialityAssessmentSubjectType $subjectType = MaterialityAssessmentSubjectType::Property,
        string $carbonFootprintId = 'footprint-id',
        string $accountId = 'account-id',
        int $year = 2025,
        ?MaterialitySectorId $sectorId = null,
        ?array $topics = null,
        ?array $questions = null,
        int $value = 3,
    ): MaterialityAssessment {
        $materialityAssessment = self::draft($id, $subjectType, $carbonFootprintId, $accountId, $year, $sectorId, $topics, $questions);

        self::answerAll($materialityAssessment, $value);

        return $materialityAssessment;
    }

    /**
     * @param list<MaterialityTopic>|null    $topics
     * @param list<MaterialityQuestion>|null $questions
     */
    public static function completed(
        ?string $id = null,
        MaterialityAssessmentSubjectType $subjectType = MaterialityAssessmentSubjectType::Property,
        string $carbonFootprintId = 'footprint-id',
        string $accountId = 'account-id',
        int $year = 2025,
        ?MaterialitySectorId $sectorId = null,
        ?array $topics = null,
        ?array $questions = null,
        int $value = 3,
        ?MaterialityDecisionRule $rule = null,
    ): MaterialityAssessment {
        $materialityAssessment = self::answered($id, $subjectType, $carbonFootprintId, $accountId, $year, $sectorId, $topics, $questions, $value);
        $materialityAssessment->complete(UserId::generate(), $rule ?? MaterialityDecisionRule::default());

        return $materialityAssessment;
    }

    public static function completedForFootprint(
        MaterialityAssessmentSubjectType $subjectType,
        string $carbonFootprintId,
        int $year,
        string $accountId = 'account-id',
    ): MaterialityAssessment {
        return self::completed(subjectType: $subjectType, carbonFootprintId: $carbonFootprintId, accountId: $accountId, year: $year);
    }

    public static function answerAll(MaterialityAssessment $materialityAssessment, int $value): void
    {
        foreach ($materialityAssessment->topics() as $topic) {
            $values = [];

            foreach ($materialityAssessment->answersOf($topic->topicId()) as $answer) {
                $values[(string) $answer->id()] = $value;
            }

            $materialityAssessment->answerTopic($topic->id(), $values);
        }
    }

    /** @return list<MaterialityAssessmentAnswer> */
    public static function answersOfFirstTopic(MaterialityAssessment $materialityAssessment): array
    {
        return $materialityAssessment->answersOf($materialityAssessment->topics()[0]->topicId());
    }

    public static function random(): MaterialityAssessment
    {
        return self::draft(carbonFootprintId: 'footprint-' . rand(100, 999));
    }
}
