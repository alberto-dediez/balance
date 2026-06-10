<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue\Remove;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\AlertCheckQueue\AlertCheckQueueAuthorizer;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;

class RemoveAlertCheckQueueService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertCheckQueueRepository $alertCheckQueueRepository,
        private readonly AlertCheckQueueAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveAlertCheckQueueRequest $request, User $user): RemoveAlertCheckQueueResponse
    {
        $entry = $this->alertCheckQueueRepository->find($request->alertCheckQueueId);

        if (null === $entry) {
            return new RemoveAlertCheckQueueResponse($request->alertCheckQueueId);
        }

        $this->authorizer->denyUnlessCanDoThat($entry, Action::Remove, $user);

        $entry->remove();
        $this->alertCheckQueueRepository->remove($entry);

        return new RemoveAlertCheckQueueResponse($entry->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveAlertCheckQueueRequest;
    }
}
