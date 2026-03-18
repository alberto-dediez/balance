<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class AccountIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'account_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return AccountId::class;
    }
}
