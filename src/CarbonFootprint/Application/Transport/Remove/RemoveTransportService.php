<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Remove;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private TransportRepository $repository, private TransportAuthorizer $authorizer) {}

    public function __invoke(RemoveTransportRequest $request, User $user): RemoveTransportResponse
    {
        $transport = $this->repository->find($request->transportId);

        if (null == $transport) {
            throw new \InvalidArgumentException('Transport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($transport, SecurityAction::Remove, $user);

        $transport->remove();

        $this->repository->add($transport);

        return new RemoveTransportResponse(
            transportId: $transport->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveTransportRequest;
    }
}
