<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\HumanResources;

use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class HumanResourcesContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->client->reset();
    }

    private function getEmployeeRepository(): EmployeeRepository
    {
        return $this->client->getContainer()->get(EmployeeRepository::class);
    }

    #[Given('employee :name with email :email and id :id exists')]
    public function employeeWithEmailAndIdExists(string $name, string $email, string $id): void
    {
        $employee = EmployeeMother::fromNameEmailAndId($name, $email, $id);

        $this->getEmployeeRepository()->add($employee);
    }
}
