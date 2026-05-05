<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\ApproveDraft;

use App\FoodLoss\Application\PreventionPlan\ApproveDraft\ApproveDraftPreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\ApproveDraft\ApproveDraftPreventionPlanService;
use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ApproveDraftPreventionPlanServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testApproveDraftMarksVersionAsDraftApproved(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::withGeneratedSections('Plan 1', 'plan-1', 'account-id-test');
        $repository->add($preventionPlan);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $this->createSUT($repository)(new ApproveDraftPreventionPlanRequest($preventionPlan->id()), $user);

        $persisted = $repository->find($preventionPlan->id());
        $this->assertNotNull($persisted);
        $this->assertEquals(PreventionPlanVersionStatus::DraftApproved, $persisted->currentPlanVersion()->status());
        $this->assertTrue($persisted->currentPlanVersion()->isDraftApproved());
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PreventionPlanRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new ApproveDraftPreventionPlanRequest(
            PreventionPlanMother::registeredPreventionPlan()->id(),
        )));
    }

    private function createSUT(PreventionPlanRepository $repository): ApproveDraftPreventionPlanService
    {
        return new ApproveDraftPreventionPlanService(
            preventionPlanRepository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
