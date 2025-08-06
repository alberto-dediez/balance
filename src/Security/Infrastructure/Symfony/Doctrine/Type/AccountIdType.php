<?php

namespace App\Security\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class AccountIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'account_id';
    }

    public function getClassName(): string
    {
        return AccountId::class;
    }
}