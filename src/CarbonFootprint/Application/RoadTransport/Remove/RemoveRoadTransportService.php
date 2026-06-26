<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Remove;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveRoadTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private RoadTransportRepository $repository, private RoadTransportAuthorizer $authorizer) {}

    public function __invoke(RemoveRoadTransportRequest $request, User $user): RemoveRoadTransportResponse
    {
        $roadTransport = $this->repository->find($request->roadTransportId);

        if (null == $roadTransport) {
            throw new \InvalidArgumentException('Road transport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($roadTransport, SecurityAction::Remove, $user);

        $roadTransport->remove();

        $this->repository->add($roadTransport);

        return new RemoveRoadTransportResponse(
            roadTransportId: $roadTransport->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveRoadTransportRequest;
    }
}
