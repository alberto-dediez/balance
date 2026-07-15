<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenEventCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ReopenEventCarbonFootprintRequest $request, User $user): void
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        if (!$eventCarbonFootprint->status()->isCompleted()) {
            throw new \InvalidArgumentException('Only a completed EventCarbonFootprint can be reopened');
        }

        $eventCarbonFootprint->reopen();

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReopenEventCarbonFootprintRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenEventCarbonFootprintRequest;
    }
}
