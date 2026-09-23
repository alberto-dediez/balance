<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenPropertyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ReopenPropertyCarbonFootprintRequest $request, User $user): void
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        if (!$this->authorizer->canDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user)
            && !$this->authorizer->canDoThat($propertyCarbonFootprint, SecurityAction::Admin, $user)
        ) {
            throw new UnauthorizedActionException($propertyCarbonFootprint, SecurityAction::Edit);
        }

        if (!$propertyCarbonFootprint->isCompleted()) {
            throw new \InvalidArgumentException('Only a completed PropertyCarbonFootprint can be reopened');
        }

        $propertyCarbonFootprint->reopen();

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReopenPropertyCarbonFootprintRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenPropertyCarbonFootprintRequest;
    }
}
