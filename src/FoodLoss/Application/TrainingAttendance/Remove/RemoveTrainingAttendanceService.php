<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\Remove;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceFileRemover;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveTrainingAttendanceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TrainingAttendanceRepository $trainingAttendanceRepository,
        private TrainingAttendanceAuthorizer $authorizer,
        private TrainingAttendanceFileRemover $fileRemover,
    ) {}

    public function __invoke(RemoveTrainingAttendanceRequest $request, User $user): RemoveTrainingAttendanceResponse
    {
        $attendance = $this->trainingAttendanceRepository->find($request->trainingAttendanceId);

        if (null === $attendance) {
            throw new \InvalidArgumentException('TrainingAttendance not found');
        }

        $this->authorizer->denyUnlessCanDoThat($attendance, SecurityAction::Remove, $user);

        $filePath = $attendance->filePath();

        $attendance->remove();

        $this->trainingAttendanceRepository->add($attendance);

        if (0 === $this->trainingAttendanceRepository->countByFilePath($filePath)) {
            $this->fileRemover->remove($filePath);
        }

        return new RemoveTrainingAttendanceResponse(trainingAttendanceId: $attendance->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveTrainingAttendanceRequest;
    }
}
