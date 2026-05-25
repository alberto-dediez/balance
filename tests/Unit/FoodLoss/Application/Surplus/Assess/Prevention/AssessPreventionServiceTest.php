<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Assess\Prevention;

use App\FoodLoss\Application\Surplus\Assess\Prevention\AssessPreventionRequest;
use App\FoodLoss\Application\Surplus\Assess\Prevention\AssessPreventionService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusPreventionAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssessPreventionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAssessPreventionYes(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessPreventionRequest($surplus->id(), true, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertTrue($updated->preventionAssessment()->canBePrevented());
        $this->assertNull($updated->destination());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusPreventionAssessed::class, $lastEvent);
        $this->assertTrue($lastEvent->canBePrevented());
    }

    public function testAssessPreventionNo(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessPreventionRequest($surplus->id(), false, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertFalse($updated->preventionAssessment()->canBePrevented());
        $this->assertNull($updated->destination());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusPreventionAssessed::class, $lastEvent);
        $this->assertFalse($lastEvent->canBePrevented());
    }

    public function testThrowsWhenSurplusNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::random();

        $user = UserMother::adminUser();
        $this->createSUT($repository)(
            new AssessPreventionRequest($surplus->id(), true, null),
            $user,
        );
    }

    private function createSUT(SurplusRepository $repository): AssessPreventionService
    {
        return new AssessPreventionService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
