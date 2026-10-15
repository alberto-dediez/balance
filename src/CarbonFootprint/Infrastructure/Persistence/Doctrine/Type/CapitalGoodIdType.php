<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CapitalGoodIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'capital_good_id';
    }

    public function getClassName(): string
    {
        return CapitalGoodId::class;
    }
}
