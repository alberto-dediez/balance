<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Symfony\Twig;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EmployeeExtension extends AbstractExtension
{
    /** @var Employee[] */
    private array $employees = [];

    public function __construct(private readonly EmployeeRepository $employeeRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('employee_name', $this->employeeName(...)),
        ];
    }

    public function employeeName(?EmployeeId $employeeId): ?Employee
    {
        if (null === $employeeId) {
            return null;
        }

        if (!isset($this->employees[(string) $employeeId])) {
            $employee = $this->employeeRepository->find($employeeId);

            if ($employee instanceof Employee) {
                $this->employees[(string) $employeeId] = $employee;
            }
        }

        return $this->employees[(string) $employeeId] ?? null;
    }
}
