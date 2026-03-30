<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

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
