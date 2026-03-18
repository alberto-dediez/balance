<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class UserIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'user_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return UserId::class;
    }
}
