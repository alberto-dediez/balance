<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance\Remove;

use App\FoodLoss\Application\TrainingAttendance\Remove\RemoveTrainingAttendanceRequest;
use App\FoodLoss\Application\TrainingAttendance\Remove\RemoveTrainingAttendanceService;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceFileRemover;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRemoved;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveTrainingAttendanceServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemoveAttendance(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $attendance = TrainingAttendanceMother::forAccount('account-test');
        $repository->add($attendance);

        $remover = $this->createFileRemover();
        $sut = $this->createSUT($repository, $remover);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $result = $sut(new RemoveTrainingAttendanceRequest($attendance->id()), $user);

        $this->assertTrue($attendance->id()->equals($result->trainingAttendanceId));
        $this->assertNull($repository->find($attendance->id()));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(TrainingAttendanceRemoved::class, $lastEvent);
    }

    public function testOrphanPdfIsDeletedWhenLastAttendanceIsRemoved(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $sessionId = (string) TrainingSessionId::generate();
        $filePath = 'training_attendances/acc/shared.pdf';

        $attendance = TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-1', 'account-test', $filePath);
        $repository->add($attendance);

        $removedPaths = [];
        $remover = $this->createFileRemover($removedPaths);
        $sut = $this->createSUT($repository, $remover);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $sut(new RemoveTrainingAttendanceRequest($attendance->id()), $user);

        $this->assertContains($filePath, $removedPaths);
    }

    public function testPdfIsNotDeletedWhenOtherAttendancesStillReferenceIt(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $sessionId = (string) TrainingSessionId::generate();
        $filePath = 'training_attendances/acc/shared.pdf';

        $att1 = TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-1', 'account-test', $filePath);
        $att2 = TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-2', 'account-test', $filePath);
        $repository->add($att1);
        $repository->add($att2);

        $removedPaths = [];
        $remover = $this->createFileRemover($removedPaths);
        $sut = $this->createSUT($repository, $remover);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $sut(new RemoveTrainingAttendanceRequest($att1->id()), $user);

        $this->assertNotContains($filePath, $removedPaths);
    }

    public function testIsHandled(): void
    {
        $attendance = TrainingAttendanceMother::registered();
        $sut = $this->createSUT(TrainingAttendanceRepositoryMother::inMemory(), $this->createFileRemover());

        $this->assertTrue($sut->isHandled(new RemoveTrainingAttendanceRequest($attendance->id())));
    }

    /** @param array<string> $removedPaths */
    private function createFileRemover(array &$removedPaths = []): TrainingAttendanceFileRemover
    {
        return new class ($removedPaths) implements TrainingAttendanceFileRemover {
            /** @param array<string> $removedPaths */
            public function __construct(private array &$removedPaths) {}

            public function remove(string $filePath): void
            {
                $this->removedPaths[] = $filePath;
            }

            /** @return array<string> */
            public function removedPaths(): array
            {
                return $this->removedPaths;
            }
        };
    }

    private function createSUT(TrainingAttendanceRepository $repository, TrainingAttendanceFileRemover $fileRemover): RemoveTrainingAttendanceService
    {
        return new RemoveTrainingAttendanceService(
            trainingAttendanceRepository: $repository,
            authorizer: new TrainingAttendanceAuthorizer(),
            fileRemover: $fileRemover,
        );
    }
}
