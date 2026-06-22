<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Application\CommandBus\Command;

class CancelTrainingAttendanceSignatureProcessRequest implements Command
{
    public function __construct(public readonly SignatureRequestId $signatureRequestId) {}
}
