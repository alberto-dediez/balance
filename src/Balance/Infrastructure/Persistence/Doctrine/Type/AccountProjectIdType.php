<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class AccountProjectIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'account_project_id';
    }

    public function getClassName(): string
    {
        return AccountProjectId::class;
    }
}
