<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession\Register;

use App\FoodLoss\Application\TrainingSession\Register\RegisterTrainingSessionRequest;
use App\FoodLoss\Application\TrainingSession\Register\RegisterTrainingSessionService;
use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRegistered;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterTrainingSessionServiceTest extends TestCase
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
        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new RegisterTrainingSessionRequest(
            sessionDate: new \DateTimeImmutable('2024-03-15'),
            title: 'Reunión sobre protocolo de donación',
            topic: 'Protocolo de donación de alimentos',
            accountId: new AccountId('account-test'),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->trainingSessionId);
        $this->assertEquals(36, strlen((string) $result->trainingSessionId));
        $this->assertEquals('Reunión sobre protocolo de donación', $result->title);

        $persisted = $repository->find($result->trainingSessionId);
        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TrainingSessionRegistered);
        $this->assertEquals('Reunión sobre protocolo de donación', $lastEvent->title());
        $this->assertTrue($persisted->id()->equals($lastEvent->trainingSessionId()));
    }

    public function testIsHandle(): void
    {
        $repository = TrainingSessionRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterTrainingSessionRequest(
            title: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(TrainingSessionRepository $repository): RegisterTrainingSessionService
    {
        return new RegisterTrainingSessionService(
            trainingSessionRepository: $repository,
            authorizer: new TrainingSessionAuthorizer(),
        );
    }
}
