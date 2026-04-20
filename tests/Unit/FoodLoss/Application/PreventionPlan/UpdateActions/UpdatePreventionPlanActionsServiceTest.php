<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\UpdateActions;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Application\PreventionPlan\UpdateActions\ProposedActionData;
use App\FoodLoss\Application\PreventionPlan\UpdateActions\UpdatePreventionPlanActionsRequest;
use App\FoodLoss\Application\PreventionPlan\UpdateActions\UpdatePreventionPlanActionsService;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionAdded;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdatePreventionPlanActionsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAddProposedAction(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::forAccount('account-id-test');
        $repository->add($preventionPlan);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $propertyId = new PropertyId('property-1');
        $actionId = new ActionId('action-1');
        $criticalPointId = CriticalPointId::generate();

        $request = new UpdatePreventionPlanActionsRequest(
            preventionPlanId: $preventionPlan->id(),
            proposedActions: [
                new ProposedActionData(propertyId: $propertyId, actionId: $actionId, criticalPointId: $criticalPointId),
            ],
        );

        $result = $sut($request, $user);

        $this->assertEquals($preventionPlan->id(), $result->preventionPlanId);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(ProposedActionAdded::class, $lastEvent);
    }

    public function testRemoveProposedAction(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::forAccount('account-id-test');
        $propertyId = new PropertyId('property-1');
        $actionId = new ActionId('action-1');
        $criticalPointId = CriticalPointId::generate();
        $preventionPlan->addProposedAction($propertyId, $actionId, $criticalPointId);
        $repository->add($preventionPlan);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new UpdatePreventionPlanActionsRequest(
            preventionPlanId: $preventionPlan->id(),
            proposedActions: [],
        );

        $result = $sut($request, $user);

        $this->assertEquals($preventionPlan->id(), $result->preventionPlanId);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(ProposedActionRemoved::class, $lastEvent);
    }

    public function testSyncProposedActions(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $criticalPointId = CriticalPointId::generate();
        $preventionPlan = PreventionPlanMother::forAccount('account-id-test');
        $preventionPlan->addProposedAction(new PropertyId('property-keep'), new ActionId('action-keep'), $criticalPointId);
        $preventionPlan->addProposedAction(new PropertyId('property-remove'), new ActionId('action-remove'), $criticalPointId);
        $repository->add($preventionPlan);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new UpdatePreventionPlanActionsRequest(
            preventionPlanId: $preventionPlan->id(),
            proposedActions: [
                new ProposedActionData(propertyId: new PropertyId('property-keep'), actionId: new ActionId('action-keep'), criticalPointId: $criticalPointId),
                new ProposedActionData(propertyId: new PropertyId('property-new'), actionId: new ActionId('action-new'), criticalPointId: $criticalPointId),
            ],
        );

        $sut($request, $user);

        $version = $preventionPlan->versions()->findByVersionNumber($preventionPlan->currentVersion());
        $this->assertNotNull($version);

        $remainingActions = $version->proposedActions()->toArray();
        $this->assertCount(2, $remainingActions);
    }

    public function testIsHandled(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();
        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdatePreventionPlanActionsRequest(
            preventionPlanId: $preventionPlan->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PreventionPlanRepository $repository): UpdatePreventionPlanActionsService
    {
        return new UpdatePreventionPlanActionsService(
            preventionPlanRepository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
