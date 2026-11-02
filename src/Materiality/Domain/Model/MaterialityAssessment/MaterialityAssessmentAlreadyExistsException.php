<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

final class MaterialityAssessmentAlreadyExistsException extends \DomainException
{
    public function __construct(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId)
    {
        parent::__construct(sprintf('A materiality assessment already exists for the %s carbon footprint "%s".', $subjectType->value, $carbonFootprintId));
    }
}
