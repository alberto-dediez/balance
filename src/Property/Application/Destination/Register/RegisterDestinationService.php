<?php

namespace App\Property\Application\Destination\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterDestinationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private DestinationRepository $destinationRepository, private DestinationAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterDestinationRequest $request, User $user): RegisterDestinationResponse
    {
        $id = DestinationId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Destination(
            id: DestinationId::generate(),
            countryId: $request->countryId
        ), Action::Register, $user);

        $destination = new Destination($id);
        $destination->register($request->name, $request->countryId, $request->externalId);

        $this->destinationRepository->add($destination);

        return new RegisterDestinationResponse($destination->id(), $destination->name(), $destination->countryId());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDestinationRequest;
    }
}