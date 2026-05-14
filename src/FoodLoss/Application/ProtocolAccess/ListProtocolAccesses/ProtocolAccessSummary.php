<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses;

use App\HumanResources\Domain\Model\Employee\Employee;

class ProtocolAccessSummary
{
    public function __construct(
        public Employee $employee,
        public ?\DateTimeImmutable $lastAccessedAt,
    ) {}
}
