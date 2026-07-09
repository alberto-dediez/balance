<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProviderContributionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'provider_contribution_id';
    }

    public function getClassName(): string
    {
        return ProviderContributionId::class;
    }
}
