<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess;

use App\Shared\Application\CommandBus\CommandResponse;

class CancelTrainingAttendanceSignatureProcessResponse implements CommandResponse
{
    public function __construct(public readonly int $cancelledCount) {}
}
