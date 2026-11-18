<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\ActionPlan;

use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanRequest;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Assistant\Domain\Model\ActionPlan\PlanNotExecutableException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Assistant\Application\AssistantServiceFixtures;
use App\Tests\Unit\Assistant\Domain\Model\ActionPlan\ActionPlanMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DiscardActionPlanServiceTest extends TestCase
{
    use AssistantServiceFixtures;

    protected function setUp(): void
    {
        $this->setUpAssistant();
    }

    public function testDiscardsTheProposedPlan(): void
    {
        $admin = UserMother::adminUser();
        $plan = ActionPlanMother::proposed($admin->id());
        $this->plans->add($plan);

        ($this->discardService())(new DiscardActionPlanRequest($plan->id()), $admin);

        $this->assertSame(ActionPlanStatus::Discarded, $plan->status());
        $this->assertSame(DiscardReason::User, $plan->discardReason());
    }

    public function testAnApprovedPlanCannotBeDiscarded(): void
    {
        $admin = UserMother::adminUser();
        $plan = ActionPlanMother::approved($admin->id());
        $this->plans->add($plan);

        $this->expectException(PlanNotExecutableException::class);

        ($this->discardService())(new DiscardActionPlanRequest($plan->id()), $admin);
    }

    public function testThePlanOfAnotherUserIsDenied(): void
    {
        $plan = ActionPlanMother::proposed(UserMother::superAdminUser()->id());
        $this->plans->add($plan);

        $this->expectException(UnauthorizedActionException::class);

        ($this->discardService())(new DiscardActionPlanRequest($plan->id()), UserMother::adminUser());
    }
}
