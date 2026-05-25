<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Assess;

use App\FoodLoss\Application\Surplus\Assess\AssessSurplusRequest;
use App\FoodLoss\Application\Surplus\Assess\AssessSurplusService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusAssessed;
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

class AssessSurplusServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testTransformationAssess(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::Transformation),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::Transformation, $updated->destination());
        $this->assertTrue($updated->transformationAssessment()->canBeTransformed());
        $this->assertNull($updated->transformationAssessment()->reasonId());
        $this->assertFalse($updated->humanDonationAssessment()->isAnswered());
        $this->assertFalse($updated->animalFeedAssessment()->isAnswered());
        $this->assertFalse($updated->compostingAssessment()->isAnswered());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAssessed::class, $lastEvent);
        $this->assertSame(SurplusDestination::Transformation, $lastEvent->destination());
    }

    public function testHumanDonationAssess(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::HumanDonation),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::HumanDonation, $updated->destination());
        $this->assertTrue($updated->humanDonationAssessment()->canBeHumanDonated());
        $this->assertNull($updated->humanDonationAssessment()->reasonId());
        $this->assertFalse($updated->animalFeedAssessment()->isAnswered());
        $this->assertFalse($updated->compostingAssessment()->isAnswered());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAssessed::class, $lastEvent);
        $this->assertSame(SurplusDestination::HumanDonation, $lastEvent->destination());
    }

    public function testAnimalFeedAssess(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::AnimalFeed),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::AnimalFeed, $updated->destination());
        $this->assertTrue($updated->animalFeedAssessment()->canBeAnimalFed());
        $this->assertNull($updated->animalFeedAssessment()->reasonId());
        $this->assertFalse($updated->compostingAssessment()->isAnswered());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAssessed::class, $lastEvent);
        $this->assertSame(SurplusDestination::AnimalFeed, $lastEvent->destination());
    }

    public function testCompostingAssess(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::Composting),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::Composting, $updated->destination());
        $this->assertTrue($updated->compostingAssessment()->canBeComposted());
        $this->assertNull($updated->compostingAssessment()->reasonId());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAssessed::class, $lastEvent);
        $this->assertSame(SurplusDestination::Composting, $lastEvent->destination());
    }

    public function testEliminationAssess(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::Elimination),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::Elimination, $updated->destination());
        $this->assertTrue($updated->eliminationAssessment()->canBeEliminated());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusAssessed::class, $lastEvent);
        $this->assertSame(SurplusDestination::Elimination, $lastEvent->destination());
    }

    public function testThrowsWhenSurplusNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::random();

        $user = UserMother::adminUser();
        $this->createSUT($repository)(
            new AssessSurplusRequest($surplus->id(), SurplusDestination::Transformation),
            $user,
        );
    }

    private function createSUT(SurplusRepository $repository): AssessSurplusService
    {
        return new AssessSurplusService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
