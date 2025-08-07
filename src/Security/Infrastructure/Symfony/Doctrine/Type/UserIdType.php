<?php

namespace App\Security\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class UserIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'user_id';
    }

    public function getClassName(): string
    {
        return UserId::class;
    }
}