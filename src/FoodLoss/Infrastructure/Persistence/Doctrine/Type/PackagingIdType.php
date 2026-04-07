<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PackagingIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'packaging_id';
    }

    public function getClassName(): string
    {
        return PackagingId::class;
    }
}
