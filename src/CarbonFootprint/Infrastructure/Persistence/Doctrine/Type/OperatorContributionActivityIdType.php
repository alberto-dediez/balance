<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionActivityId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class OperatorContributionActivityIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'operator_contribution_activity_id';
    }

    public function getClassName(): string
    {
        return OperatorContributionActivityId::class;
    }
}
