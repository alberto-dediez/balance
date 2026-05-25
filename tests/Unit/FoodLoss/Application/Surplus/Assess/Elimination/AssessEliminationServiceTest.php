<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Assess\Elimination;

use App\FoodLoss\Application\Surplus\Assess\Elimination\AssessEliminationRequest;
use App\FoodLoss\Application\Surplus\Assess\Elimination\AssessEliminationService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusEliminationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssessEliminationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAssessEliminationYesSetsDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessEliminationRequest($surplus->id(), true),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::Elimination, $updated->destination());
        $this->assertTrue($updated->eliminationAssessment()->canBeEliminated());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusEliminationAssessed::class, $lastEvent);
        $this->assertTrue($lastEvent->canBeEliminated());
    }

    public function testAssessEliminationNoDoesNotSetDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessEliminationRequest($surplus->id(), false),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertNull($updated->destination());
        $this->assertFalse($updated->eliminationAssessment()->canBeEliminated());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusEliminationAssessed::class, $lastEvent);
        $this->assertFalse($lastEvent->canBeEliminated());
    }

    public function testThrowsWhenSurplusNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::random();

        $user = UserMother::adminUser();
        $this->createSUT($repository)(
            new AssessEliminationRequest($surplus->id(), true),
            $user,
        );
    }

    private function createSUT(SurplusRepository $repository): AssessEliminationService
    {
        return new AssessEliminationService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
