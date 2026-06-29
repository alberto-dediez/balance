<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class OvernightStayIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'overnight_stay_id';
    }

    public function getClassName(): string
    {
        return OvernightStayId::class;
    }
}
