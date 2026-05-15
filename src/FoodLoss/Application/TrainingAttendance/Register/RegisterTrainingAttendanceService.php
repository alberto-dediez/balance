<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\Register;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceUploader;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterTrainingAttendanceService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private TrainingAttendanceRepository $trainingAttendanceRepository,
        private TrainingAttendanceAuthorizer $authorizer,
        private TrainingAttendanceUploader $uploader,
    ) {}

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RegisterTrainingAttendanceRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function __invoke(RegisterTrainingAttendanceRequest $request, User $user): void
    {
        assert($request->trainingSessionId instanceof TrainingSessionId);
        assert($request->accountId instanceof AccountId);
        assert($request->attendedAt instanceof \DateTimeImmutable);

        $dummy = new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $request->accountId);
        $this->authorizer->denyUnlessCanDoThat($dummy, SecurityAction::Register, $user);

        $filePath = $this->uploader->upload(
            $request->temporaryFilePath,
            $request->fileExtension,
            $request->accountId,
            $request->trainingSessionId,
        );

        foreach ($request->employeeIds as $employeeId) {
            $attendance = new TrainingAttendance(id: TrainingAttendanceId::generate());
            $attendance->register(
                trainingSessionId: $request->trainingSessionId,
                employeeId: $employeeId,
                accountId: $request->accountId,
                attendedAt: $request->attendedAt,
                filePath: $filePath,
            );

            $this->trainingAttendanceRepository->add($attendance);
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterTrainingAttendanceRequest;
    }
}
