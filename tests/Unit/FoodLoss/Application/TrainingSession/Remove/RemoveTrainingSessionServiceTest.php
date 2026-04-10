<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession\Remove;

use App\FoodLoss\Application\TrainingSession\Remove\RemoveTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Remove\RemoveTrainingSessionService;
use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRemoved;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveTrainingSessionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::from(
            title: 'Test session',
            id: 'test-id-123',
            accountId: 'account-test',
        );
        $repository->add($trainingSession);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $result = $sut(new RemoveTrainingSessionRequest($trainingSession->id()), $user);

        $this->assertTrue($trainingSession->id()->equals($result->trainingSessionId));
        $this->assertNull($repository->find($trainingSession->id()));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TrainingSessionRemoved);
        $this->assertTrue($trainingSession->id()->equals($lastEvent->trainingSessionId()));
    }

    public function testIsHandle(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $trainingSession = TrainingSessionMother::registeredTrainingSession();

        $result = $sut->isHandled(new RemoveTrainingSessionRequest($trainingSession->id()));

        $this->assertTrue($result);
    }

    private function createSUT(TrainingSessionRepository $repository): RemoveTrainingSessionService
    {
        return new RemoveTrainingSessionService(
            repository: $repository,
            authorizer: new TrainingSessionAuthorizer(),
        );
    }
}
