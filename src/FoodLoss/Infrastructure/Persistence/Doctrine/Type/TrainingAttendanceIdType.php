<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TrainingAttendanceIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'training_attendance_id';
    }

    public function getClassName(): string
    {
        return TrainingAttendanceId::class;
    }
}
