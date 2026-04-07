<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Remove;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveProtocolService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProtocolRepository $repository,
        private ProtocolAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveProtocolRequest $request, User $user): RemoveProtocolResponse
    {
        $protocol = $this->repository->find($request->protocolId);

        if (null == $protocol) {
            throw new \InvalidArgumentException('Protocol not found');
        }

        $this->authorizer->denyUnlessCanDoThat($protocol, SecurityAction::Remove, $user);

        $protocol->remove();

        $this->repository->add($protocol);

        return new RemoveProtocolResponse(
            protocolId: $protocol->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveProtocolRequest;
    }
}
