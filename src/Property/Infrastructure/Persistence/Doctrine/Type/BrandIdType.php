<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class BrandIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'brand_id';
    }

    public function getClassName(): string
    {
        return BrandId::class;
    }
}
