<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

final class InvalidMaterialityAnswerValueException extends \DomainException
{
    public static function outOfScale(int $value, string $answerId): self
    {
        return new self(sprintf('Value "%d" is not valid for the materiality answer "%s".', $value, $answerId));
    }

    public static function foreignAnswer(string $answerId): self
    {
        return new self(sprintf('Materiality answer "%s" does not belong to the topic.', $answerId));
    }
}
