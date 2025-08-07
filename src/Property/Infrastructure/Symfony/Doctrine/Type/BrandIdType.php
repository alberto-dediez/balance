<?php

namespace App\Property\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

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