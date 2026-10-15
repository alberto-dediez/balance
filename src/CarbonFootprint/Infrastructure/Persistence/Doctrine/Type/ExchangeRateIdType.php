<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ExchangeRateIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'exchange_rate_id';
    }

    public function getClassName(): string
    {
        return ExchangeRateId::class;
    }
}
