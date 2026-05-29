<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Remove;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveSignatureRequestService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SignatureRequestRepository $repository,
        private SignatureRequestAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveSignatureRequestRequest $request, User $user): RemoveSignatureRequestResponse
    {
        $signatureRequest = $this->repository->find($request->signatureRequestId);

        if (null === $signatureRequest) {
            throw new \InvalidArgumentException('SignatureRequest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($signatureRequest, SecurityAction::Remove, $user);

        $signatureRequest->remove();

        $this->repository->add($signatureRequest);

        return new RemoveSignatureRequestResponse(
            signatureRequestId: $signatureRequest->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveSignatureRequestRequest;
    }
}
