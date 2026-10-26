<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Register;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;

class RegisterEmailLogService
{
    public function __construct(
        private readonly EmailLogRepository $repository,
    ) {}

    public function __invoke(RegisterEmailLogRequest $request): RegisterEmailLogResponse
    {
        $emailLog = new EmailLog(EmailLogId::fromString($request->emailLogId));

        $emailLog->register(
            recipientEmail: $request->recipientEmail,
            recipientName: $request->recipientName,
            subject: $request->subject,
            template: $request->template,
            provider: $request->provider,
            providerMessageId: $request->providerMessageId,
            status: $request->status,
            statusReason: $request->statusReason,
            sentAt: $request->sentAt,
        );

        $this->repository->add($emailLog);

        return new RegisterEmailLogResponse($emailLog->id());
    }
}
