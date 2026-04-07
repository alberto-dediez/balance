<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\Remove;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Application\PreventionPlan\Remove\RemovePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Remove\RemovePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRemoved;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemovePreventionPlanServiceTest extends TestCase
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

        $result = $sut(new RemovePreventionPlanRequest($preventionPlan->id()), $user);

        $this->assertTrue($preventionPlan->id()->equals($result->preventionPlanId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PreventionPlanRemoved);
        $this->assertTrue($preventionPlan->id()->equals($lastEvent->preventionPlanId()));
    }

    public function testIsHandle(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $preventionPlan = PreventionPlanMother::registeredPreventionPlan();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemovePreventionPlanRequest($preventionPlan->id()));

        $this->assertTrue($result);
    }

    private function createSUT(PreventionPlanRepository $repository): RemovePreventionPlanService
    {
        return new RemovePreventionPlanService(
            repository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
