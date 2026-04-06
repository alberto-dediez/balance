<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateDestinationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private DestinationRepository $repository, private DestinationAuthorizer $authorizer) {}

    public function __invoke(UpdateDestinationRequest $request, User $user): UpdateDestinationResponse
    {
        $destination = $this->repository->find($request->destinationId);

        if (null == $destination) {
            throw new \InvalidArgumentException('Destination not found');
        }

        $this->authorizer->denyUnlessCanDoThat($destination, Action::Edit, $user);

        $destination->update($request->name, $request->countryId, $request->externalId);

        $this->repository->add($destination);

        assert($destination->countryId() instanceof CountryId);

        return new UpdateDestinationResponse(
            destinationId: $destination->id(),
            name: $destination->name(),
            countryId: $destination->countryId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateDestinationRequest;
    }
}
