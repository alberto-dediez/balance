<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Type;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialityAssessmentTopicIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'materiality_assessment_topic_id';
    }

    public function getClassName(): string
    {
        return MaterialityAssessmentTopicId::class;
    }
}
