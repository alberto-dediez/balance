<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Type;

use App\Payments\Domain\Model\Payout\PayoutId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PayoutIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'payout_id';
    }

    public function getClassName(): string
    {
        return PayoutId::class;
    }
}
