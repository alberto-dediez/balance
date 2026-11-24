<?php

declare(strict_types=1);

namespace App\Property\Application\Property\ReleaseAccount;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReleasePropertyAccountService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private PropertyRepository $repository, private PropertyAuthorizer $authorizer) {}

    public function __invoke(ReleasePropertyAccountRequest $request, User $user): ReleasePropertyAccountResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $this->authorizer->denyUnlessCanDoThat($property, Action::Edit, $user);

        $property->releaseAccount();

        $this->repository->add($property);

        return new ReleasePropertyAccountResponse(
            propertyId: $property->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReleasePropertyAccountRequest;
    }
}
