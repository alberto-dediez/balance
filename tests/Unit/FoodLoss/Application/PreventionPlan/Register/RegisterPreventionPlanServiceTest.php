<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\Register;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Application\PreventionPlan\Register\RegisterPreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\Register\RegisterPreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRegistered;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCreated;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterPreventionPlanServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $request = new RegisterPreventionPlanRequest(
            name: 'Plan de Prevención 1',
            properties: [],
            companyId: new CompanyId('company-id-test'),
            accountId: new AccountId('account-id-test'),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->preventionPlanId);
        $this->assertEquals(36, strlen((string) $result->preventionPlanId));
        $this->assertEquals('Plan de Prevención 1', $result->name);

        $preventionPlanPersisted = $repository->find($result->preventionPlanId);

        $this->assertNotNull($preventionPlanPersisted);

        $events = $this->spySubscriber->events();

        $this->assertCount(2, $events);

        $registeredEvent = $events[0];
        $this->assertInstanceOf(PreventionPlanRegistered::class, $registeredEvent);
        $this->assertEquals('Plan de Prevención 1', $registeredEvent->name());
        $this->assertTrue($preventionPlanPersisted->id()->equals($registeredEvent->preventionPlanId()));

        $versionCreatedEvent = $events[1];
        $this->assertInstanceOf(PreventionPlanVersionCreated::class, $versionCreatedEvent);
        $this->assertEquals(1, $versionCreatedEvent->versionNumber());
    }

    public function testIsHandle(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterPreventionPlanRequest(
            name: 'test',
            companyId: new CompanyId('company-id-test'),
            accountId: new AccountId('account-id-test'),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PreventionPlanRepository $repository): RegisterPreventionPlanService
    {
        return new RegisterPreventionPlanService(
            preventionPlanRepository: $repository,
            authorizer: new PreventionPlanAuthorizer(),
        );
    }
}
