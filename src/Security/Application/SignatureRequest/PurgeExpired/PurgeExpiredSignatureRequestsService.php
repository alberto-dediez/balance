<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\PurgeExpired;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PurgeExpiredSignatureRequestsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly SignatureRequestRepository $repository,
    ) {}

    public function __invoke(PurgeExpiredSignatureRequestsRequest $request, User $user): PurgeExpiredSignatureRequestsResponse
    {
        $pendingExpired = $this->repository->findExpired($request->now);

        $expired = 0;

        foreach ($pendingExpired as $signatureRequest) {
            $signatureRequest->expire();
            $this->repository->add($signatureRequest);
            ++$expired;
        }

        return new PurgeExpiredSignatureRequestsResponse(expired: $expired);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeExpiredSignatureRequestsRequest;
    }
}
