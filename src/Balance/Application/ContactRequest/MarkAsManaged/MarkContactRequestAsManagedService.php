<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\MarkAsManaged;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class MarkContactRequestAsManagedService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContactRequestRepository $contactRequestRepository,
        private ContactRequestAuthorizer $authorizer,
    ) {}

    public function __invoke(MarkContactRequestAsManagedRequest $request, User $user): MarkContactRequestAsManagedResponse
    {
        $contactRequest = $this->contactRequestRepository->find($request->contactRequestId);

        if (null === $contactRequest) {
            throw new \InvalidArgumentException('Contact request not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contactRequest, SecurityAction::Edit, $user);

        $contactRequest->markAsManaged($user->id(), $request->now ?? new \DateTimeImmutable('NOW'));

        $this->contactRequestRepository->add($contactRequest);

        return new MarkContactRequestAsManagedResponse(contactRequestId: $contactRequest->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MarkContactRequestAsManagedRequest;
    }
}
