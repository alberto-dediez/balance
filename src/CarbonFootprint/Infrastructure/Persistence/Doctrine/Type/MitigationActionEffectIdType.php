<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionEffectId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MitigationActionEffectIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'mitigation_action_effect_id';
    }

    public function getClassName(): string
    {
        return MitigationActionEffectId::class;
    }
}
