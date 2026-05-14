<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class IncidenceEvidenceIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'incidence_evidence_id';
    }

    public function getClassName(): string
    {
        return IncidenceEvidenceId::class;
    }
}
