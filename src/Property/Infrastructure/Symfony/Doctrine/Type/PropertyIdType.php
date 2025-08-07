<?php

namespace App\Property\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class PropertyIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'property_id';
    }

    public function getClassName(): string
    {
        return PropertyId::class;
    }
}