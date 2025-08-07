<?php

namespace App\Property\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class CountryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'country_id';
    }

    public function getClassName(): string
    {
        return CountryId::class;
    }
}