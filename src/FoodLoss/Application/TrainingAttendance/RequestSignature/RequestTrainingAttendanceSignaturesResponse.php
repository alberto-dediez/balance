<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\RequestSignature;

use App\Shared\Application\CommandBus\CommandResponse;

class RequestTrainingAttendanceSignaturesResponse implements CommandResponse
{
    public function __construct(public readonly int $requestedCount) {}
}
