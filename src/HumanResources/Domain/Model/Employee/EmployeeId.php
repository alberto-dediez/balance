<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EmployeeId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
