<?php

namespace App\Property\Application\Destination\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;

class UpdateDestinationService
{
    public function __construct(private DestinationRepository $repository, private DestinationAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateDestinationRequest $request, User $user): UpdateDestinationResponse
    {
        $destination = $this->repository->find($request->destinationId);

        if (null == $destination) {
            throw new InvalidArgumentException("Destination not found");
        }

        $this->authorizer->denyUnlessCanDoThat($destination, Action::Edit, $user);

        $destination->update($request->name, $request->countryId, $request->externalId);

        $this->repository->add($destination);

        return new UpdateDestinationResponse(
            destinationId: $destination->id(),
            name: $destination->name(),
            countryId: $destination->countryId()
        );
    }
}