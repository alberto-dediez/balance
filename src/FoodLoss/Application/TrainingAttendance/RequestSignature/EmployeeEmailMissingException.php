<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\RequestSignature;

use App\HumanResources\Domain\Model\Employee\Employee;

class EmployeeEmailMissingException extends \RuntimeException
{
    public static function forEmployee(Employee $employee): self
    {
        return new self(sprintf('Employee "%s" has no email address configured.', $employee->name()));
    }
}
