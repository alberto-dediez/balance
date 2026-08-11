<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocationId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProjectContributionCreditAllocationIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'project_contribution_credit_allocation_id';
    }

    public function getClassName(): string
    {
        return ProjectContributionCreditAllocationId::class;
    }
}
