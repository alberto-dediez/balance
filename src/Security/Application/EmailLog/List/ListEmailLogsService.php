<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\List;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\EmailLog\EmailLogAuthorizer;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use App\Security\Domain\Model\User\User;

class ListEmailLogsService
{
    public function __construct(
        private readonly EmailLogRepository $repository,
        private readonly EmailLogAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmailLogsRequest $request, User $user): ListEmailLogsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EmailLog(EmailLogId::generate()), SecurityAction::List, $user);

        $emailLogs = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListEmailLogsResponse($emailLogs, $totalResults);
    }
}
