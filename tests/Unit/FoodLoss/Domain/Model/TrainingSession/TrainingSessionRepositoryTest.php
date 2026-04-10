<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionFilter;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TrainingSessionRepositoryTest extends KernelTestCase
{
    public function testAddTrainingSessionToRepository(): void
    {
        $repository = $this->createSUT();

        $trainingSession = TrainingSessionMother::registeredTrainingSession();

        $this->assertNull($repository->find($trainingSession->id()));

        $repository->add($trainingSession);

        $recoveredTrainingSession = $repository->find($trainingSession->id());

        $this->assertNotNull($recoveredTrainingSession);
        $this->assertEquals($trainingSession->id(), $recoveredTrainingSession->id());
    }

    public function testRemoveTrainingSessionFromRepository(): void
    {
        $repository = $this->createSUT();

        $trainingSession = TrainingSessionMother::registeredTrainingSession();

        $repository->add($trainingSession);

        $recoveredTrainingSession = $repository->find($trainingSession->id());

        $this->assertNotNull($recoveredTrainingSession);

        $repository->remove($trainingSession);

        $this->assertNull($repository->find($trainingSession->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(TrainingSessionMother::random());
        $repository->add(TrainingSessionMother::random());
        $repository->add(TrainingSessionMother::random());
        $repository->add(TrainingSessionMother::fromTitle('Sesión específica'));

        $filter = new TrainingSessionFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);

        $filter = new TrainingSessionFilter(
            title: 'Sesión específica',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithAccountId(): void
    {
        $repository = $this->createSUT();

        $repository->add(TrainingSessionMother::forAccount('account-test'));
        $repository->add(TrainingSessionMother::forAccount('account-test'));
        $repository->add(TrainingSessionMother::forAccount('other-account'));

        $filter = new TrainingSessionFilter(accountId: new AccountId('account-test'));

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(TrainingSessionMother::random());
        $repository->add(TrainingSessionMother::random());
        $repository->add(TrainingSessionMother::random());

        $filter = new TrainingSessionFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $trainingSession = TrainingSessionMother::registeredTrainingSession();

        $repository->add($trainingSession);

        $recoveredTrainingSession = $repository->find($trainingSession->id());

        $this->assertNotNull($recoveredTrainingSession);

        $recoveredTrainingSession->remove();

        $repository->add($trainingSession);

        $this->assertNull($repository->find($trainingSession->id()));
    }

    abstract protected function createSUT(): TrainingSessionRepository;
}
