<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenCompanyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ReopenCompanyCarbonFootprintRequest $request, User $user): void
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        if (!$this->authorizer->canDoThat($companyCarbonFootprint, SecurityAction::Edit, $user)
            && !$this->authorizer->canDoThat($companyCarbonFootprint, SecurityAction::Admin, $user)
        ) {
            throw new UnauthorizedActionException($companyCarbonFootprint, SecurityAction::Edit);
        }

        if (!$companyCarbonFootprint->isCompleted()) {
            throw new \InvalidArgumentException('Only a completed CompanyCarbonFootprint can be reopened');
        }

        $companyCarbonFootprint->reopen();

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReopenCompanyCarbonFootprintRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenCompanyCarbonFootprintRequest;
    }
}
