<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance\Register;

use App\FoodLoss\Application\TrainingAttendance\Register\RegisterTrainingAttendanceRequest;
use App\FoodLoss\Application\TrainingAttendance\Register\RegisterTrainingAttendanceService;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceUploader;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRegistered;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterTrainingAttendanceServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterCreatesOneAttendancePerEmployee(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sessionId = TrainingSessionId::generate();

        $request = new RegisterTrainingAttendanceRequest(
            trainingSessionId: $sessionId,
            accountId: $accountId,
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            employeeIds: [
                new EmployeeId('emp-1'),
                new EmployeeId('emp-2'),
                new EmployeeId('emp-3'),
            ],
            temporaryFilePath: '/tmp/test.pdf',
            fileExtension: 'pdf',
        );

        $sut($request, $user);

        $attendances = $repository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $sessionId, limit: 0),
        );

        $this->assertCount(3, $attendances);

        foreach ($attendances as $attendance) {
            $this->assertEquals('training_attendances/account-test/uploaded.pdf', $attendance->filePath());
            $this->assertEquals('2024-03-15', $attendance->attendedAt()->format('Y-m-d'));
        }

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(TrainingAttendanceRegistered::class, $lastEvent);
    }

    public function testAllAttendancesShareTheSameFilePath(): void
    {
        $repository = TrainingAttendanceRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sessionId = TrainingSessionId::generate();

        $request = new RegisterTrainingAttendanceRequest(
            trainingSessionId: $sessionId,
            accountId: $accountId,
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            employeeIds: [new EmployeeId('emp-1'), new EmployeeId('emp-2')],
            temporaryFilePath: '/tmp/test.pdf',
            fileExtension: 'pdf',
        );

        $sut($request, $user);

        $attendances = $repository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $sessionId, limit: 0),
        );

        $filePaths = array_unique(array_map(fn($a) => $a->filePath(), $attendances));
        $this->assertCount(1, $filePaths);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(TrainingAttendanceRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RegisterTrainingAttendanceRequest()));
    }

    private function createSUT(TrainingAttendanceRepository $repository): RegisterTrainingAttendanceService
    {
        $uploader = new class implements TrainingAttendanceUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId, TrainingSessionId $trainingSessionId): string
            {
                return 'training_attendances/' . $accountId . '/uploaded.pdf';
            }
        };

        return new RegisterTrainingAttendanceService(
            trainingAttendanceRepository: $repository,
            authorizer: new TrainingAttendanceAuthorizer(
                EmployeeRepositoryMother::inMemory()
            ),
            uploader: $uploader,
        );
    }
}
