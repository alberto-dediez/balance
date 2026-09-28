<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

final class InvalidMaterialityQuestionWeightException extends \DomainException
{
    public function __construct(int $weight)
    {
        parent::__construct(sprintf('Materiality question weight must be at least 1, "%d" given.', $weight));
    }
}
