<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\RequestSignature;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestService;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RequestSignaturePreventionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private EmployeeRepository $employeeRepository,
        private RegisterSignatureRequestService $registerSignatureRequestService,
        private RemoveSignatureRequestService $removeSignatureRequestService,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof RequestSignaturePreventionPlanRequest;
    }

    public function __invoke(RequestSignaturePreventionPlanRequest $request, User $user): null
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (!$preventionPlan instanceof PreventionPlan) {
            return null;
        }

        $version = $preventionPlan->versions()->findByVersionNumber($request->versionNumber);

        if (!$version instanceof PreventionPlanVersion) {
            return null;
        }

        if (null !== $version->signatureRequestId()) {
            try {
                ($this->removeSignatureRequestService)(new RemoveSignatureRequestRequest(
                    signatureRequestId: $version->signatureRequestId(),
                ), $user);
            } catch (\Throwable) {

            }
        }

        $responsible = $version->responsible();

        if (null === $responsible) {
            return null;
        }

        $employee = $this->employeeRepository->find($responsible);

        if (null === $employee || '' === $employee->email()) {
            return null;
        }

        $signatureResponse = ($this->registerSignatureRequestService)(
            new RegisterSignatureRequestRequest(
                documentToSignPath: $request->pdfPath,
                signerName: $employee->name(),
                signerLocale: LocaleGetter::locale(),
                signerEmail: $employee->email(),
                notificationChannel: NotificationChannel::Email,
                accountId: $preventionPlan->accountId(),
                otpRequired: false,
            ),
            $user,
        );

        $preventionPlan->requestSignature($signatureResponse->signatureRequestId, $request->versionNumber);
        $this->preventionPlanRepository->add($preventionPlan);

        return null;
    }
}
