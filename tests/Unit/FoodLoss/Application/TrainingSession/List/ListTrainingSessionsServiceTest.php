<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession\List;

use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsRequest;
use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsService;
use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryTrainingAttendanceRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListTrainingSessionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $repository->add(TrainingSessionMother::forAccount('account-test'));
        $repository->add(TrainingSessionMother::forAccount('account-test'));
        $repository->add(TrainingSessionMother::forAccount('other-account'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new ListTrainingSessionsRequest(accountId: new AccountId('account-test'));

        $result = $sut($request, $user);

        $this->assertCount(2, $result->trainingSessions);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testUserCanList(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $repository->add(TrainingSessionMother::forAccount('account-test'));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));

        $request = new ListTrainingSessionsRequest(accountId: new AccountId('account-test'));

        $result = $sut($request, $user);

        $this->assertCount(1, $result->trainingSessions);
    }

    private function createSUT(TrainingSessionRepository $repository): ListTrainingSessionsService
    {
        return new ListTrainingSessionsService(
            repository: $repository,
            authorizer: new TrainingSessionAuthorizer(new InMemoryTrainingAttendanceRepository()),
        );
    }
}
