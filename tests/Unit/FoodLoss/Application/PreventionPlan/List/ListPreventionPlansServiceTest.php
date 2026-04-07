<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\List;

use App\FoodLoss\Application\PreventionPlan\List\ListPreventionPlansRequest;
use App\FoodLoss\Application\PreventionPlan\List\ListPreventionPlansService;
use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPreventionPlansServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $repository->add(PreventionPlanMother::forAccount('account-id-test'));
        $repository->add(PreventionPlanMother::forAccount('account-id-test'));
        $repository->add(PreventionPlanMother::forAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new ListPreventionPlansRequest(
            accountId: new AccountId('account-id-test'),
        );

        $result = $sut($request, $user);

        $this->assertCount(2, $result->preventionPlans);
        $this->assertEquals(2, $result->totalResults);
    }

    private function createSUT(PreventionPlanRepository $repository): ListPreventionPlansService
    {
        return new ListPreventionPlansService(
            repository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
