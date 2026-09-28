<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

class MaterialityQuestionMother
{
    public static function registeredMaterialityQuestion(): MaterialityQuestion
    {
        return self::fromText('materiality-question-text');
    }

    public static function fromText(string $text): MaterialityQuestion
    {
        return self::from(
            text: $text,
            id: (string) MaterialityQuestionId::generate(),
        );
    }

    public static function fromTextAndId(string $text, string $id): MaterialityQuestion
    {
        return self::from(
            text: $text,
            id: $id,
        );
    }

    public static function from(
        string $text,
        string $id,
        MaterialityCriterion $criterion = MaterialityCriterion::Magnitude,
        ?MaterialityTopicId $topicId = null,
        MaterialityAnswerScale $answerScale = MaterialityAnswerScale::Level,
        int $weight = 1,
        int $position = 0,
    ): MaterialityQuestion {
        $materialityQuestion = new MaterialityQuestion(
            id: new MaterialityQuestionId($id),
        );
        $materialityQuestion->register(
            criterion: $criterion,
            topicId: $topicId,
            text: $text,
            help: $text . ' help',
            answerScale: $answerScale,
            weight: $weight,
            position: $position,
        );

        return $materialityQuestion;
    }

    public static function forTopic(
        MaterialityTopicId $topicId,
        string $text = 'materiality-question-text',
        MaterialityCriterion $criterion = MaterialityCriterion::Magnitude,
    ): MaterialityQuestion {
        return self::from(
            text: $text,
            id: (string) MaterialityQuestionId::generate(),
            criterion: $criterion,
            topicId: $topicId,
        );
    }

    public static function random(): MaterialityQuestion
    {
        return self::from(
            text: 'materiality-question-' . rand(100, 999),
            id: (string) MaterialityQuestionId::generate(),
        );
    }
}
