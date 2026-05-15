<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TrainingAttendanceRepositoryTest extends KernelTestCase
{
    public function testAddAttendanceToRepository(): void
    {
        $repository = $this->createSUT();

        $attendance = TrainingAttendanceMother::registered();

        $this->assertNull($repository->find($attendance->id()));

        $repository->add($attendance);

        $recovered = $repository->find($attendance->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($attendance->id(), $recovered->id());
    }

    public function testFindDoesNotReturnRemovedAttendance(): void
    {
        $repository = $this->createSUT();

        $attendance = TrainingAttendanceMother::registered();
        $repository->add($attendance);

        $this->assertNotNull($repository->find($attendance->id()));

        $attendance->remove();
        $repository->add($attendance);

        $this->assertNull($repository->find($attendance->id()));
    }

    public function testFindByFilterByTrainingSession(): void
    {
        $repository = $this->createSUT();
        $sessionId = (string) TrainingSessionId::generate();

        $repository->add(TrainingAttendanceMother::forSession($sessionId));
        $repository->add(TrainingAttendanceMother::forSession($sessionId));
        $repository->add(TrainingAttendanceMother::random());

        $filter = new TrainingAttendanceFilter(
            trainingSessionId: new TrainingSessionId($sessionId),
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByAccount(): void
    {
        $repository = $this->createSUT();

        $repository->add(TrainingAttendanceMother::forAccount('account-a'));
        $repository->add(TrainingAttendanceMother::forAccount('account-a'));
        $repository->add(TrainingAttendanceMother::forAccount('account-b'));

        $filter = new TrainingAttendanceFilter(
            accountId: new AccountId('account-a'),
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterWithLimitZeroReturnsAll(): void
    {
        $repository = $this->createSUT();

        $repository->add(TrainingAttendanceMother::random());
        $repository->add(TrainingAttendanceMother::random());
        $repository->add(TrainingAttendanceMother::random());

        $filter = new TrainingAttendanceFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindByFilterDoesNotReturnRemovedAttendances(): void
    {
        $repository = $this->createSUT();

        $attendance = TrainingAttendanceMother::registered();
        $repository->add($attendance);
        $attendance->remove();
        $repository->add($attendance);

        $filter = new TrainingAttendanceFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(0, $result);
    }

    public function testCountByFilePathIgnoresRemovedAttendances(): void
    {
        $repository = $this->createSUT();
        $sessionId = (string) TrainingSessionId::generate();
        $filePath = 'training_attendances/acc/shared.pdf';

        $att1 = TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-1', 'acc', $filePath);
        $att2 = TrainingAttendanceMother::forSessionAndEmployee($sessionId, 'emp-2', 'acc', $filePath);
        $repository->add($att1);
        $repository->add($att2);

        $this->assertEquals(2, $repository->countByFilePath($filePath));

        $att1->remove();
        $repository->add($att1);

        $this->assertEquals(1, $repository->countByFilePath($filePath));

        $att2->remove();
        $repository->add($att2);

        $this->assertEquals(0, $repository->countByFilePath($filePath));
    }

    abstract protected function createSUT(): TrainingAttendanceRepository;
}
