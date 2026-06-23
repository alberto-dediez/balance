<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CancelTrainingAttendanceSignatureProcessService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
        private readonly TrainingAttendanceAuthorizer $authorizer,
    ) {}

    public function __invoke(CancelTrainingAttendanceSignatureProcessRequest $request, User $user): CancelTrainingAttendanceSignatureProcessResponse
    {
        $attendances = $this->trainingAttendanceRepository->findBySignatureRequestId($request->signatureRequestId);

        foreach ($attendances as $attendance) {
            $this->authorizer->denyUnlessCanDoThat($attendance, SecurityAction::Edit, $user);
        }

        foreach ($attendances as $attendance) {
            $attendance->cancelSignatureProcess();
            $attendance->remove();
            $this->trainingAttendanceRepository->add($attendance);
        }

        return new CancelTrainingAttendanceSignatureProcessResponse(cancelledCount: count($attendances));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CancelTrainingAttendanceSignatureProcessRequest;
    }
}
