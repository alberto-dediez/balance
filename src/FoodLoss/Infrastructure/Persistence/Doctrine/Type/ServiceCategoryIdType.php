<?php

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use Doctrine\DBAL\Types\Type;

class ServiceCategoryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'service_category_id';
    }

    public function getClassName(): string
    {
        return ServiceCategoryId::class;
    }
}
