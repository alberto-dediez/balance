<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use PHPUnit\Framework\TestCase;

class PreventionPlanTest extends TestCase
{
    public function testNewVersionInheritsProposedActionsFromPreviousVersion(): void
    {
        $propertyA = new PropertyId('property-a');
        $propertyB = new PropertyId('property-b');
        $actionId = new ActionId((string) ActionId::generate());
        $criticalPointId = CriticalPointId::generate();

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionId, $criticalPointId);
        $plan->addProposedAction($propertyB, $actionId, $criticalPointId);

        $this->assertCount(2, $plan->currentPlanVersion()->proposedActions());

        // Update keeping only propertyA — version bumps, propertyB is removed
        $plan->update(
            name: 'Plan',
            propertyIds: [$propertyA],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
        );

        $this->assertEquals(2, $plan->currentVersion());

        $newVersion = $plan->currentPlanVersion();

        // propertyA action is inherited, propertyB action is not
        $this->assertCount(1, $newVersion->proposedActions());
        $this->assertTrue($newVersion->proposedActions()->has($propertyA, $actionId, $criticalPointId));
        $this->assertFalse($newVersion->proposedActions()->has($propertyB, $actionId, $criticalPointId));
    }

    public function testNewVersionInheritsAllProposedActionsWhenNoPropertyIsRemoved(): void
    {
        $propertyA = new PropertyId('property-a');
        $propertyB = new PropertyId('property-b');
        $actionA = new ActionId((string) ActionId::generate());
        $actionB = new ActionId((string) ActionId::generate());
        $criticalPointIdA = CriticalPointId::generate();
        $criticalPointIdB = CriticalPointId::generate();

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionA, $criticalPointIdA);
        $plan->addProposedAction($propertyB, $actionB, $criticalPointIdB);

        // Update with same properties but different name — version bumps
        $propertyC = new PropertyId('property-c');
        $plan->update(
            name: 'Plan Updated',
            propertyIds: [$propertyA, $propertyB, $propertyC],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
        );

        $this->assertEquals(2, $plan->currentVersion());

        $newVersion = $plan->currentPlanVersion();

        // Both actions are inherited
        $this->assertCount(2, $newVersion->proposedActions());
        $this->assertTrue($newVersion->proposedActions()->has($propertyA, $actionA, $criticalPointIdA));
        $this->assertTrue($newVersion->proposedActions()->has($propertyB, $actionB, $criticalPointIdB));
    }

    public function testNoNewVersionCreatedWhenPropertiesDoNotChange(): void
    {
        $propertyA = new PropertyId('property-a');
        $actionId = new ActionId((string) ActionId::generate());
        $criticalPointId = CriticalPointId::generate();

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->addProposedAction($propertyA, $actionId, $criticalPointId);

        $plan->update(
            name: 'Plan Renamed',
            propertyIds: [$propertyA],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
        );

        // No version bump — properties unchanged
        $this->assertEquals(1, $plan->currentVersion());
        $this->assertCount(1, $plan->versions());
    }

    public function testNoNewVersionWhenUpdatingWithSameExplicitProperties(): void
    {
        $propertyA = new PropertyId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        $propertyB = new PropertyId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
        );

        $plan->update(
            name: 'Plan Renamed',
            propertyIds: [
                new PropertyId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
                new PropertyId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
            ],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
        );

        $this->assertEquals(1, $plan->currentVersion());
    }

    public function testNoNewVersionWhenUpdatingUseAllCompanyPropertiesWithSameSnapshot(): void
    {
        $propertyA = new PropertyId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        $propertyB = new PropertyId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
            useAllCompanyProperties: true,
        );

        $plan->update(
            name: 'Plan',
            propertyIds: [
                new PropertyId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
                new PropertyId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
            ],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
            useAllCompanyProperties: true,
        );

        $this->assertEquals(1, $plan->currentVersion());
    }

    public function testNewVersionCreatedWhenCompanyPropertyAddedInAllPropertiesMode(): void
    {
        $propertyA = new PropertyId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        $propertyB = new PropertyId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');
        $propertyC = new PropertyId('cccccccc-cccc-cccc-cccc-cccccccccccc');

        $plan = new PreventionPlan(id: PreventionPlanId::generate());
        $plan->register(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-id'),
            registeredBy: new UserId('user-id'),
            useAllCompanyProperties: true,
        );

        // Company added a new property — controller resolves [A, B, C]
        $plan->update(
            name: 'Plan',
            propertyIds: [$propertyA, $propertyB, $propertyC],
            companyId: new CompanyId('company-id'),
            updatedBy: new UserId('user-id'),
            useAllCompanyProperties: true,
        );

        $this->assertEquals(2, $plan->currentVersion());
        $this->assertCount(3, $plan->currentPlanVersion()->propertyIds());
    }
}
