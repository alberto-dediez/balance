<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MerchandisingItemIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'merchandising_item_id';
    }

    public function getClassName(): string
    {
        return MerchandisingItemId::class;
    }
}
