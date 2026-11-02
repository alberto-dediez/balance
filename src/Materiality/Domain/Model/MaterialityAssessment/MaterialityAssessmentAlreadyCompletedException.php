<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

final class MaterialityAssessmentAlreadyCompletedException extends \DomainException
{
    public function __construct(MaterialityAssessmentId $materialityAssessmentId)
    {
        parent::__construct(sprintf('Materiality assessment "%s" is already completed.', (string) $materialityAssessmentId));
    }
}
