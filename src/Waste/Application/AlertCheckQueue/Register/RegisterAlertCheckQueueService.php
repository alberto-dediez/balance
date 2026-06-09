<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\AlertCheckQueue\AlertCheckQueueAuthorizer;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueue;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;

class RegisterAlertCheckQueueService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertCheckQueueRepository $alertCheckQueueRepository,
        private readonly AlertCheckQueueAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterAlertCheckQueueRequest $request, User $user): RegisterAlertCheckQueueResponse
    {
        $id = AlertCheckQueueId::generate();
        $entry = new AlertCheckQueue(id: $id, accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($entry, Action::Register, $user);

        if ($this->alertCheckQueueRepository->existsFor($request->accountId, $request->checkDate)) {
            return new RegisterAlertCheckQueueResponse(null);
        }

        $entry->register($request->accountId, $request->checkDate);
        $this->alertCheckQueueRepository->add($entry);

        return new RegisterAlertCheckQueueResponse($id);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAlertCheckQueueRequest;
    }
}
