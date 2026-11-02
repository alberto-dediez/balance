<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Type;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialityAssessmentIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'materiality_assessment_id';
    }

    public function getClassName(): string
    {
        return MaterialityAssessmentId::class;
    }
}
