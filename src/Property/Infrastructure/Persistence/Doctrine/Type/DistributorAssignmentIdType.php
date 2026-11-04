<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DistributorAssignmentIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'distributor_assignment_id';
    }

    public function getClassName(): string
    {
        return DistributorAssignmentId::class;
    }
}
