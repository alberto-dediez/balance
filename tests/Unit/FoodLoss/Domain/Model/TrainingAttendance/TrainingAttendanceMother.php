<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;

class TrainingAttendanceMother
{
    public static function registered(): TrainingAttendance
    {
        return self::from(
            trainingSessionId: (string) TrainingSessionId::generate(),
            employeeId: (string) EmployeeId::generate(),
            accountId: 'account-id',
        );
    }

    public static function from(
        string $trainingSessionId,
        string $employeeId,
        string $accountId = 'account-id',
        string $filePath = 'training_attendances/account-id/sheet.pdf',
        ?\DateTimeImmutable $attendedAt = null,
    ): TrainingAttendance {
        $attendance = new TrainingAttendance(id: TrainingAttendanceId::generate());
        $attendance->register(
            trainingSessionId: new TrainingSessionId($trainingSessionId),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId($accountId),
            attendedAt: $attendedAt ?? new \DateTimeImmutable('2024-03-15'),
            filePath: $filePath,
        );

        return $attendance;
    }

    public static function forSession(string $trainingSessionId, string $accountId = 'account-id'): TrainingAttendance
    {
        return self::from(
            trainingSessionId: $trainingSessionId,
            employeeId: (string) EmployeeId::generate(),
            accountId: $accountId,
        );
    }

    public static function forSessionAndEmployee(string $trainingSessionId, string $employeeId, string $accountId = 'account-id', string $filePath = 'training_attendances/account-id/sheet.pdf'): TrainingAttendance
    {
        return self::from(
            trainingSessionId: $trainingSessionId,
            employeeId: $employeeId,
            accountId: $accountId,
            filePath: $filePath,
        );
    }

    public static function forAccount(string $accountId): TrainingAttendance
    {
        return self::from(
            trainingSessionId: (string) TrainingSessionId::generate(),
            employeeId: (string) EmployeeId::generate(),
            accountId: $accountId,
        );
    }

    public static function random(): TrainingAttendance
    {
        return self::from(
            trainingSessionId: (string) TrainingSessionId::generate(),
            employeeId: (string) EmployeeId::generate(),
        );
    }
}
