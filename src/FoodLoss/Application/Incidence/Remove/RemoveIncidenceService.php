<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Remove;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveIncidenceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveIncidenceRequest $request, User $user): RemoveIncidenceResponse
    {
        $incidence = $this->incidenceRepository->find($request->incidenceId);

        if (null == $incidence) {
            throw new \InvalidArgumentException('Incidence not found');
        }

        $this->authorizer->denyUnlessCanDoThat($incidence, SecurityAction::Remove, $user);

        $incidence->remove();

        $this->incidenceRepository->add($incidence);

        return new RemoveIncidenceResponse(
            incidenceId: $incidence->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveIncidenceRequest;
    }
}
