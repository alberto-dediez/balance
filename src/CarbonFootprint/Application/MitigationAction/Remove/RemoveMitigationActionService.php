<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Remove;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMitigationActionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationActionAuthorizer $authorizer,
        private MitigationPlanFootprints $footprints,
    ) {}

    public function __invoke(RemoveMitigationActionRequest $request, User $user): RemoveMitigationActionResponse
    {
        $mitigationAction = $this->mitigationActionRepository->find($request->mitigationActionId);

        if (null == $mitigationAction) {
            throw new \InvalidArgumentException('Mitigation action not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationAction, SecurityAction::Remove, $user);

        $this->footprints->ensureIsEditable(
            $this->footprints->find($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId()),
        );

        $mitigationAction->remove();

        $this->mitigationActionRepository->add($mitigationAction);

        return new RemoveMitigationActionResponse($mitigationAction->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMitigationActionRequest;
    }
}
