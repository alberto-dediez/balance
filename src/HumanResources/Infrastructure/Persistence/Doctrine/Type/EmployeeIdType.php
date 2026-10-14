<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Persistence\Doctrine\Type;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class EmployeeIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'employee_id';
    }

    public function getClassName(): string
    {
        return EmployeeId::class;
    }
}
