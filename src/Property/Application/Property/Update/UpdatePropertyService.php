<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Update;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdatePropertyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private PropertyRepository $repository, private PropertyAuthorizer $authorizer) {}

    public function __invoke(UpdatePropertyRequest $request, User $user): UpdatePropertyResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $this->authorizer->denyUnlessCanDoThat($property, Action::Edit, $user);

        $property->update(
            name: $request->name,
            accountId: $request->accountId,
            info: $request->info,
            externalId: $request->externalId,
        );

        $this->repository->add($property);

        return new UpdatePropertyResponse(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId(),
            info: $property->info(),
            externalId: $property->externalId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePropertyRequest;
    }
}
