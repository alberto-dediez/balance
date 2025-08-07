<?php

namespace App\Property\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class CategoryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'category_id';
    }

    public function getClassName(): string
    {
        return CategoryId::class;
    }
}