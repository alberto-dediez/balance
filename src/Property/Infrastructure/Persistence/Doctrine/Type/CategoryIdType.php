<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

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
