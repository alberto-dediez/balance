<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonFilter;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseFilter;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;

class GetAdminDashboardService
{
    public function __construct(
        private readonly AdminDashboardAuthorizer $authorizer,
        private readonly AccountRepository $accountRepository,
        private readonly UserRepository $userRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly ActionRepository $actionRepository,
        private readonly ReasonRepository $reasonRepository,
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
        private readonly SurplusCauseRepository $surplusCauseRepository,
    ) {}

    public function __invoke(GetAdminDashboardRequest $request, User $user): GetAdminDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat($request, SecurityAction::List, $user);

        $response = new GetAdminDashboardResponse();

        $this->fillAccounts($response);
        $this->fillUsers($response);
        $this->fillProperties($response);
        $this->fillFoodLoss($response);

        return $response;
    }

    private function fillAccounts(GetAdminDashboardResponse $response): void
    {
        $response->totalAccounts = $this->accountRepository->totalResultsByFilter(new AccountFilter(limit: 0));
        $response->activeAccounts = $this->accountRepository->totalResultsByFilter(new AccountFilter(active: true, limit: 0));
    }

    private function fillUsers(GetAdminDashboardResponse $response): void
    {
        $response->totalUsers = $this->userRepository->totalResultsByFilter(new UserFilter(limit: 0));
        $response->totalEmployees = $this->employeeRepository->totalResultsByFilter(new EmployeeFilter(limit: 0));
    }

    private function fillProperties(GetAdminDashboardResponse $response): void
    {
        $response->totalProperties = $this->propertyRepository->totalResultsByFilter(new PropertyFilter(limit: 0));
        $response->totalCompanies = $this->companyRepository->totalResultsByFilter(new CompanyFilter(limit: 0));
    }

    private function fillFoodLoss(GetAdminDashboardResponse $response): void
    {
        $response->totalActions = $this->actionRepository->totalResultsByFilter(new ActionFilter(limit: 0));
        $response->totalReasons = $this->reasonRepository->totalResultsByFilter(new ReasonFilter(limit: 0));
        $response->totalServiceCategories = $this->serviceCategoryRepository->totalResultsByFilter(new ServiceCategoryFilter(limit: 0));
        $response->totalSurplusCauses = $this->surplusCauseRepository->totalResultsByFilter(new SurplusCauseFilter(limit: 0));

        $countByPriority = [];
        foreach (Priority::cases() as $priority) {
            $count = $this->actionRepository->totalResultsByFilter(new ActionFilter(priority: $priority, limit: 0));
            if ($count > 0) {
                $countByPriority[$priority->value] = $count;
            }
        }

        $response->actionsByPriority = DashboardChart::fromCounts($countByPriority);
    }
}
