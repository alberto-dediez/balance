<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Register;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterIncidenceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterIncidenceRequest $request, User $user): RegisterIncidenceResponse
    {
        $id = IncidenceId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Incidence(id: $id), SecurityAction::List, $user);

        $incidence = new Incidence($id);
        $incidence->register(
            name: $request->name,
            exceptionOccurredOn: $request->exceptionOccurredOn,
            incidenceType: $request->incidenceType,
            description: $request->description,
            effect: $request->effect,
            propertyId: $request->propertyId,
            accountId: $request->accountId,
        );

        $this->incidenceRepository->add($incidence);

        return new RegisterIncidenceResponse(
            incidenceId: $incidence->id(),
            name: $incidence->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterIncidenceRequest;
    }
}
