<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Update;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateIncidenceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateIncidenceRequest $request, User $user): UpdateIncidenceResponse
    {
        $incidence = $this->incidenceRepository->find($request->incidenceId);

        if (null == $incidence) {
            throw new \InvalidArgumentException('Incidence not found');
        }

        $this->authorizer->denyUnlessCanDoThat($incidence, SecurityAction::Edit, $user);

        $incidence->update(
            name: $request->name,
            exceptionOccurredOn: $request->exceptionOccurredOn,
            incidenceType: $request->incidenceType,
            description: $request->description,
            effect: $request->effect,
            propertyId: $request->propertyId,
        );

        $this->incidenceRepository->add($incidence);

        return new UpdateIncidenceResponse(
            incidenceId: $incidence->id(),
            name: $incidence->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateIncidenceRequest;
    }
}
