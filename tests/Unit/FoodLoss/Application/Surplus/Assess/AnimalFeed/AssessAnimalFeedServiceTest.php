<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Assess\AnimalFeed;

use App\FoodLoss\Application\Surplus\Assess\AnimalFeed\AssessAnimalFeedRequest;
use App\FoodLoss\Application\Surplus\Assess\AnimalFeed\AssessAnimalFeedService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusAnimalFeedAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssessAnimalFeedServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAssessAnimalFeedYesSetsDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessAnimalFeedRequest($surplus->id(), true, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::AnimalFeed, $updated->destination());
        $this->assertTrue($updated->animalFeedAssessment()->canBeAnimalFed());
        $this->assertFalse($updated->compostingAssessment()->isAnswered());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAnimalFeedAssessed::class, $lastEvent);
        $this->assertTrue($lastEvent->canBeAnimalFed());
    }

    public function testAssessAnimalFeedNoDoesNotSetDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessAnimalFeedRequest($surplus->id(), false, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertNull($updated->destination());
        $this->assertFalse($updated->animalFeedAssessment()->canBeAnimalFed());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAnimalFeedAssessed::class, $lastEvent);
        $this->assertFalse($lastEvent->canBeAnimalFed());
    }

    public function testThrowsWhenSurplusNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::random();

        $user = UserMother::adminUser();
        $this->createSUT($repository)(
            new AssessAnimalFeedRequest($surplus->id(), true, null),
            $user,
        );
    }

    private function createSUT(SurplusRepository $repository): AssessAnimalFeedService
    {
        return new AssessAnimalFeedService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
