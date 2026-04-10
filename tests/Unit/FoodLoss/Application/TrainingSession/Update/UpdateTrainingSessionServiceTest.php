<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession\Update;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Application\TrainingSession\Update\UpdateTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Update\UpdateTrainingSessionService;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateTrainingSessionServiceTest extends TestCase
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
            title: 'Original title',
            id: 'test-id-123',
            accountId: 'account-test',
        );
        $repository->add($trainingSession);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new UpdateTrainingSessionRequest(
            trainingSessionId: $trainingSession->id(),
            sessionDate: new \DateTimeImmutable('2024-04-01'),
            title: 'Updated title',
            topic: 'Updated topic',
        );

        $result = $sut($request, $user);

        $this->assertEquals('Updated title', $result->title);

        $persisted = $repository->find($trainingSession->id());
        $this->assertNotNull($persisted);
        $this->assertEquals('Updated title', $persisted->title());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TrainingSessionUpdated);
        $this->assertEquals('Updated title', $lastEvent->title());
    }

    public function testIsHandle(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $trainingSession = TrainingSessionMother::registeredTrainingSession();

        $result = $sut->isHandled(new UpdateTrainingSessionRequest(
            trainingSessionId: $trainingSession->id(),
            sessionDate: new \DateTimeImmutable('NOW'),
            title: 'test',
            topic: '',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(TrainingSessionRepository $repository): UpdateTrainingSessionService
    {
        return new UpdateTrainingSessionService(
            trainingSessionRepository: $repository,
            authorizer: new TrainingSessionAuthorizer(),
        );
    }
}
