<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\Update;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Application\PreventionPlan\Update\UpdatePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Update\UpdatePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdatePreventionPlanServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::forAccount('account-id-test');
        $repository->add($preventionPlan);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $request = new UpdatePreventionPlanRequest(
            preventionPlanId: $preventionPlan->id(),
            name: 'Plan Actualizado',
            properties: [],
        );

        $result = $sut($request, $user);

        $this->assertEquals('Plan Actualizado', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PreventionPlanUpdated);
        $this->assertEquals('Plan Actualizado', $lastEvent->name());
    }

    public function testIsHandle(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdatePreventionPlanRequest(
            preventionPlanId: $preventionPlan->id(),
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PreventionPlanRepository $repository): UpdatePreventionPlanService
    {
        return new UpdatePreventionPlanService(
            preventionPlanRepository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
