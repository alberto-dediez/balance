<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\Dashboard;

use App\FoodLoss\Application\PreventionPlan\Dashboard\GetPreventionPlanDashboardRequest;
use App\FoodLoss\Application\PreventionPlan\Dashboard\GetPreventionPlanDashboardService;
use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetPreventionPlanDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id';
    private const COMPANY_ID = '00000000-0000-0000-0000-000000000001';
    private const COMPANY_ID_2 = '00000000-0000-0000-0000-000000000002';

    public function testUseCase(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();

        $propertyIdA1 = PropertyId::generate();
        $propertyIdA2 = PropertyId::generate();

        $preventionPlanRepository->add(PreventionPlanMother::from(
            name: 'Plan A',
            id: (string) PreventionPlanId::generate(),
            accountId: self::ACCOUNT_ID,
            companyId: self::COMPANY_ID,
            propertyIds: [$propertyIdA1, $propertyIdA2],
        ));

        $completed = PreventionPlanMother::from(
            name: 'Plan B',
            id: (string) PreventionPlanId::generate(),
            accountId: self::ACCOUNT_ID,
            companyId: self::COMPANY_ID,
        );
        $completed->markAsCompleted();
        $preventionPlanRepository->add($completed);

        $withoutResponsible = PreventionPlanMother::withoutResponsible();
        $preventionPlanRepository->add($withoutResponsible);

        $preventionPlanRepository->add(PreventionPlanMother::random());

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Property 1', self::ACCOUNT_ID, (string) $propertyIdA1));
        $propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Property 2', self::ACCOUNT_ID, (string) $propertyIdA2));
        $propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Property without plan', self::ACCOUNT_ID, (string) PropertyId::generate()));

        $sut = $this->createSUT($preventionPlanRepository, $propertyRepository);

        $request = new GetPreventionPlanDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(3, $response->totalPreventionPlans);
        $this->assertSame(1, $response->completedPreventionPlans);
        $this->assertSame(33.3, $response->completedPercentage());
        $this->assertSame(2, $response->inProgressPreventionPlans);
        $this->assertSame(2, $response->withResponsiblePreventionPlans);
        $this->assertSame(66.7, $response->withResponsiblePercentage());

        $statusCounts = array_combine($response->byStatus->labels, $response->byStatus->values);
        $this->assertSame(2, $statusCounts['InProgress']);
        $this->assertSame(1, $statusCounts['Completed']);
        $this->assertSame(0, $statusCounts['Validated']);
        $this->assertSame(0, $statusCounts['Abandoned']);

        $this->assertSame([(new \DateTimeImmutable('NOW'))->format('m/Y')], $response->registrationsByMonth->labels);
        $this->assertSame([3], $response->registrationsByMonth->values);

        $byCompany = array_combine($response->byCompany->labels, $response->byCompany->values);
        $this->assertSame(1, $byCompany['Con plan']);
        $this->assertSame(1, $byCompany['Sin plan']);

        $properties = array_combine($response->properties->labels, $response->properties->values);
        $this->assertSame(2, $properties['Con plan']);
        $this->assertSame(1, $properties['Sin plan']);
    }

    public function testFiltersByCompany(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();
        $preventionPlanRepository->add(PreventionPlanMother::from(
            name: 'Plan A',
            id: (string) PreventionPlanId::generate(),
            accountId: self::ACCOUNT_ID,
            companyId: self::COMPANY_ID,
        ));
        $preventionPlanRepository->add(PreventionPlanMother::from(
            name: 'Plan B',
            id: (string) PreventionPlanId::generate(),
            accountId: self::ACCOUNT_ID,
            companyId: 'another-company-id',
        ));

        $sut = $this->createSUT($preventionPlanRepository);

        $request = new GetPreventionPlanDashboardRequest(accountId: $accountId, companyId: new CompanyId(self::COMPANY_ID));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(1, $response->totalPreventionPlans);

        $byCompany = array_combine($response->byCompany->labels, $response->byCompany->values);
        $this->assertSame(1, $byCompany['Con plan']);
        $this->assertSame(1, $byCompany['Sin plan']);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT(PreventionPlanRepositoryMother::inMemory());

        $request = new GetPreventionPlanDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(PreventionPlanRepository $preventionPlanRepository, ?InMemoryPropertyRepository $propertyRepository = null): GetPreventionPlanDashboardService
    {
        $companyRepository = CompanyRepositoryMother::inMemory();
        $companyRepository->add(CompanyMother::fromNameAndAccountAndId('ACME', self::ACCOUNT_ID, self::COMPANY_ID));
        $companyRepository->add(CompanyMother::fromNameAndAccountAndId('Other Company', self::ACCOUNT_ID, self::COMPANY_ID_2));

        return new GetPreventionPlanDashboardService(
            repository: $preventionPlanRepository,
            authorizer: new PreventionPlanAuthorizer(),
            companyRepository: $companyRepository,
            propertyRepository: $propertyRepository ?? PropertyRepositoryMother::inMemory(),
        );
    }
}
