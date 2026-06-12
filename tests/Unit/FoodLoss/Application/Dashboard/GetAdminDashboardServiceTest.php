<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\AdminDashboardAuthorizer;
use App\FoodLoss\Application\Dashboard\GetAdminDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetAdminDashboardService;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryActionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryReasonRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryServiceCategoryRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemorySurplusCauseRepository;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class GetAdminDashboardServiceTest extends TestCase
{
    public function testAccountKpis(): void
    {
        $accountRepository = AccountRepositoryMother::inMemory();
        $accountRepository->add(AccountMother::random());
        $accountRepository->add(AccountMother::random());
        $accountRepository->add(AccountMother::random());

        $sut = $this->createSUT(accountRepository: $accountRepository);

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertSame(3, $response->totalAccounts);
        $this->assertSame(3, $response->activeAccounts);
    }

    public function testUserKpis(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $userRepository->add(UserMother::registeredUser());
        $userRepository->add(UserMother::fromUsername('another-user'));

        $sut = $this->createSUT(userRepository: $userRepository);

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertSame(2, $response->totalUsers);
    }

    public function testEmployeeKpis(): void
    {
        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::registeredEmployee());
        $employeeRepository->add(EmployeeMother::random());
        $employeeRepository->add(EmployeeMother::random());

        $sut = $this->createSUT(employeeRepository: $employeeRepository);

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertSame(3, $response->totalEmployees);
    }

    public function testPropertyKpis(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromName('Restaurante A'));
        $propertyRepository->add(PropertyMother::fromName('Restaurante B'));

        $companyRepository = CompanyRepositoryMother::inMemory();
        $companyRepository->add(CompanyMother::fromName('Empresa A'));
        $companyRepository->add(CompanyMother::fromName('Empresa B'));
        $companyRepository->add(CompanyMother::fromName('Empresa C'));

        $sut = $this->createSUT(propertyRepository: $propertyRepository, companyRepository: $companyRepository);

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertSame(2, $response->totalProperties);
        $this->assertSame(3, $response->totalCompanies);
    }

    public function testFoodLossKpis(): void
    {
        $actionRepository = ActionRepositoryMother::inMemory();
        $actionRepository->add(ActionMother::random());
        $actionRepository->add(ActionMother::random());

        $reasonRepository = ReasonRepositoryMother::inMemory();
        $reasonRepository->add(ReasonMother::random());
        $reasonRepository->add(ReasonMother::random());
        $reasonRepository->add(ReasonMother::random());

        $serviceCategoryRepository = ServiceCategoryRepositoryMother::inMemory();
        $serviceCategoryRepository->add(ServiceCategoryMother::random());

        $surplusCauseRepository = SurplusCauseRepositoryMother::inMemory();
        $surplusCauseRepository->add(SurplusCauseMother::random());
        $surplusCauseRepository->add(SurplusCauseMother::random());
        $surplusCauseRepository->add(SurplusCauseMother::random());
        $surplusCauseRepository->add(SurplusCauseMother::random());

        $sut = $this->createSUT(
            actionRepository: $actionRepository,
            reasonRepository: $reasonRepository,
            serviceCategoryRepository: $serviceCategoryRepository,
            surplusCauseRepository: $surplusCauseRepository,
        );

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertSame(2, $response->totalActions);
        $this->assertSame(3, $response->totalReasons);
        $this->assertSame(1, $response->totalServiceCategories);
        $this->assertSame(4, $response->totalSurplusCauses);
    }

    public function testActionsByPriorityChart(): void
    {
        $actionRepository = ActionRepositoryMother::inMemory();
        $actionRepository->add(ActionMother::fromPriority(Priority::Prevention));
        $actionRepository->add(ActionMother::fromPriority(Priority::Prevention));
        $actionRepository->add(ActionMother::fromPriority(Priority::Composting));

        $sut = $this->createSUT(actionRepository: $actionRepository);

        $response = $sut(new GetAdminDashboardRequest(), $this->admin());

        $this->assertContains(Priority::Prevention->value, $response->actionsByPriority->labels);
        $this->assertContains(Priority::Composting->value, $response->actionsByPriority->labels);
        $this->assertNotContains(Priority::Transformation->value, $response->actionsByPriority->labels);

        $preventionIndex = array_search(Priority::Prevention->value, $response->actionsByPriority->labels, true);
        $this->assertIsInt($preventionIndex);
        $this->assertSame(2, $response->actionsByPriority->values[$preventionIndex]);
    }

    public function testUnauthorizedUser(): void
    {
        $sut = $this->createSUT();

        $this->expectException(UnauthorizedActionException::class);

        $sut(new GetAdminDashboardRequest(), UserMother::ownerUser());
    }

    private function admin(): \App\Security\Domain\Model\User\User
    {
        return UserMother::adminUser();
    }

    private function createSUT(
        ?InMemoryAccountRepository $accountRepository = null,
        ?InMemoryUserRepository $userRepository = null,
        ?InMemoryEmployeeRepository $employeeRepository = null,
        ?InMemoryPropertyRepository $propertyRepository = null,
        ?InMemoryCompanyRepository $companyRepository = null,
        ?InMemoryActionRepository $actionRepository = null,
        ?InMemoryReasonRepository $reasonRepository = null,
        ?InMemoryServiceCategoryRepository $serviceCategoryRepository = null,
        ?InMemorySurplusCauseRepository $surplusCauseRepository = null,
    ): GetAdminDashboardService {
        return new GetAdminDashboardService(
            authorizer: new AdminDashboardAuthorizer(),
            accountRepository: $accountRepository ?? AccountRepositoryMother::inMemory(),
            userRepository: $userRepository ?? UserRepositoryMother::inMemory(),
            employeeRepository: $employeeRepository ?? EmployeeRepositoryMother::inMemory(),
            propertyRepository: $propertyRepository ?? PropertyRepositoryMother::inMemory(),
            companyRepository: $companyRepository ?? CompanyRepositoryMother::inMemory(),
            actionRepository: $actionRepository ?? ActionRepositoryMother::inMemory(),
            reasonRepository: $reasonRepository ?? ReasonRepositoryMother::inMemory(),
            serviceCategoryRepository: $serviceCategoryRepository ?? ServiceCategoryRepositoryMother::inMemory(),
            surplusCauseRepository: $surplusCauseRepository ?? SurplusCauseRepositoryMother::inMemory(),
        );
    }
}
