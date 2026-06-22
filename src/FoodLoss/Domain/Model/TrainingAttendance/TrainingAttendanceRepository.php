<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;

interface TrainingAttendanceRepository
{
    public function add(TrainingAttendance $trainingAttendance): void;

    public function find(TrainingAttendanceId $trainingAttendanceId): ?TrainingAttendance;

    /** @return array<TrainingAttendance> */
    public function findByFilter(TrainingAttendanceFilter $filter): array;

    public function totalResultsByFilter(TrainingAttendanceFilter $filter): int;

    public function countByFilePath(string $filePath): int;

    /** @return array<TrainingAttendance> */
    public function findBySignatureRequestId(SignatureRequestId $signatureRequestId): array;
}
