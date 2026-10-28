<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionSourceId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class OrganizationalProviderContributionSourceIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'organizational_provider_contribution_source_id';
    }

    public function getClassName(): string
    {
        return OrganizationalProviderContributionSourceId::class;
    }
}
