<?php

namespace App\Property\Application\Destination\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;

class RegisterDestinationService
{
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
}