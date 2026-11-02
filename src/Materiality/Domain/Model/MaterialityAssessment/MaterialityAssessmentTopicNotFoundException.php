<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

final class MaterialityAssessmentTopicNotFoundException extends \DomainException
{
    public function __construct(MaterialityAssessmentTopicId $assessmentTopicId)
    {
        parent::__construct(sprintf('Materiality assessment topic "%s" not found.', (string) $assessmentTopicId));
    }
}
