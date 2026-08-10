<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Type;

use App\Payments\Domain\Model\Payment\PaymentId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PaymentIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'payment_id';
    }

    public function getClassName(): string
    {
        return PaymentId::class;
    }
}
