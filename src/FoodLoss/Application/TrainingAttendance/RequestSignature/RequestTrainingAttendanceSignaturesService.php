<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\RequestSignature;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingSessionPdfGenerator;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\Translation\LocaleSwitcher;

class RequestTrainingAttendanceSignaturesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
        private readonly TrainingSessionRepository $trainingSessionRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly TrainingAttendanceAuthorizer $authorizer,
        private readonly TrainingSessionPdfGenerator $pdfGenerator,
        private readonly RegisterSignatureRequestService $registerSignatureRequestService,
        private readonly UserRepository $userRepository,
        private LocaleSwitcher $localeSwitcher,
    ) {}

    public function __invoke(RequestTrainingAttendanceSignaturesRequest $request, User $user): RequestTrainingAttendanceSignaturesResponse
    {
        assert(null !== $request->accountId);
        assert(null !== $request->trainingSessionId);

        $dummy = new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $request->accountId);
        $this->authorizer->denyUnlessCanDoThat($dummy, SecurityAction::Register, $user);

        $trainingSession = $this->trainingSessionRepository->find($request->trainingSessionId);

        if (null === $trainingSession) {
            throw new \InvalidArgumentException('Training session not found.');
        }

        $count = 0;

        foreach ($request->employeeIds as $employeeId) {
            $employee = $this->employeeRepository->find($employeeId);

            if (null === $employee) {
                continue;
            }

            $this->processEmployee($request, $trainingSession, $employee, $user);
            ++$count;
        }

        return new RequestTrainingAttendanceSignaturesResponse(requestedCount: $count);
    }

    private function processEmployee(
        RequestTrainingAttendanceSignaturesRequest $request,
        TrainingSession $trainingSession,
        Employee $employee,
        User $user,
    ): void {
        if (empty($employee->email())) {
            throw EmployeeEmailMissingException::forEmployee($employee);
        }

        $existing = $this->trainingAttendanceRepository->findByFilter(
            new TrainingAttendanceFilter(
                trainingSessionId: $request->trainingSessionId,
                employeeId: $employee->id(),
                limit: 0,
            ),
        );

        foreach ($existing as $attendance) {
            if ($attendance->isInSignatureProcess() || '' !== $attendance->filePath()) {
                return;
            }
        }

        $employeeLocale = $this->resolveSignerLocale($employee);

        $pdfPath = $this->localeSwitcher->runWithLocale($employeeLocale->value, function () use ($trainingSession, $employee) {
            return $this->pdfGenerator->generate($trainingSession, $employee);
        });

        assert(null !== $request->accountId);
        assert(null !== $request->trainingSessionId);

        $signatureRequest = new RegisterSignatureRequestRequest(
            documentToSignPath: $pdfPath,
            signerName: $employee->name(),
            signerLocale: $employeeLocale,
            signerEmail: $employee->email(),
            notificationChannel: NotificationChannel::Email,
            accountId: $request->accountId,
            otpRequired: false,
        );

        $signatureResponse = ($this->registerSignatureRequestService)($signatureRequest, $user);

        $attendance = new TrainingAttendance(id: TrainingAttendanceId::generate());
        $attendance->startSignatureProcess(
            trainingSessionId: $request->trainingSessionId,
            employeeId: $employee->id(),
            accountId: $request->accountId,
            attendedAt: $trainingSession->sessionDate(),
            signatureRequestId: $signatureResponse->signatureRequestId,
        );

        $this->trainingAttendanceRepository->add($attendance);
    }

    private function resolveSignerLocale(Employee $employee): Locale
    {
        $userId = $employee->userId();

        if (null !== $userId) {
            $user = $this->userRepository->find($userId);
            $preferredLocale = $user?->preferredLocale();

            if (null !== $preferredLocale) {
                return $preferredLocale;
            }
        }

        return LocaleGetter::locale();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RequestTrainingAttendanceSignaturesRequest;
    }
}
