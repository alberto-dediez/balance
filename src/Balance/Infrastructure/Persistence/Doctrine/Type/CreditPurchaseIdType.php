<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CreditPurchaseIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'credit_purchase_id';
    }

    public function getClassName(): string
    {
        return CreditPurchaseId::class;
    }
}
