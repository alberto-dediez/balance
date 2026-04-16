<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use PHPUnit\Framework\TestCase;

class PreventionPlanTest extends TestCase
{
    public function testNewVersionInheritsProposedActionsFromPreviousVersion(): void
    {
        $propertyA = new PropertyId('property-a');
        $propertyB = new PropertyId('property-b');
        $actionId = new ActionId((string) ActionId::generate());

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionId);
        $plan->addProposedAction($propertyB, $actionId);

        $this->assertCount(2, $plan->currentPlanVersion()->proposedActions());

        // Update keeping only propertyA — version bumps, propertyB is removed
        $plan->update(
            name: 'Plan',
            propertyIds: [$propertyA],
            updatedBy: new UserId('user-id'),
        );

        $this->assertEquals(1, $plan->currentVersion());

        $newVersion = $plan->currentPlanVersion();

        // propertyA action is inherited, propertyB action is not
        $this->assertCount(1, $newVersion->proposedActions());
        $this->assertTrue($newVersion->proposedActions()->has($propertyA, $actionId));
        $this->assertFalse($newVersion->proposedActions()->has($propertyB, $actionId));
    }

    public function testNewVersionInheritsAllProposedActionsWhenNoPropertyIsRemoved(): void
    {
        $propertyA = new PropertyId('property-a');
        $propertyB = new PropertyId('property-b');
        $actionA = new ActionId((string) ActionId::generate());
        $actionB = new ActionId((string) ActionId::generate());

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionA);
        $plan->addProposedAction($propertyB, $actionB);

        // Update with same properties but different name — version bumps
        $propertyC = new PropertyId('property-c');
        $plan->update(
            name: 'Plan Updated',
            propertyIds: [$propertyA, $propertyB, $propertyC],
            updatedBy: new UserId('user-id'),
        );

        $this->assertEquals(1, $plan->currentVersion());

        $newVersion = $plan->currentPlanVersion();

        // Both actions are inherited
        $this->assertCount(2, $newVersion->proposedActions());
        $this->assertTrue($newVersion->proposedActions()->has($propertyA, $actionA));
        $this->assertTrue($newVersion->proposedActions()->has($propertyB, $actionB));
    }

    public function testNoNewVersionCreatedWhenPropertiesDoNotChange(): void
    {
        $propertyA = new PropertyId('property-a');
        $actionId = new ActionId((string) ActionId::generate());

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA],
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionId);

        $plan->update(
            name: 'Plan Renamed',
            propertyIds: [$propertyA],
            updatedBy: new UserId('user-id'),
        );

        // No version bump — properties unchanged
        $this->assertEquals(0, $plan->currentVersion());
        $this->assertCount(1, $plan->versions());
    }
}
