<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Type;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PayeeIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'payee_id';
    }

    public function getClassName(): string
    {
        return PayeeId::class;
    }
}
