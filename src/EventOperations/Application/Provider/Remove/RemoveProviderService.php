<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Remove;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveProviderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ProviderRepository $repository, private ProviderAuthorizer $authorizer) {}

    public function __invoke(RemoveProviderRequest $request, User $user): RemoveProviderResponse
    {
        $provider = $this->repository->find($request->providerId);

        if (null == $provider) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $this->authorizer->denyUnlessCanDoThat($provider, SecurityAction::Remove, $user);

        $provider->remove();

        $this->repository->add($provider);

        return new RemoveProviderResponse(
            providerId: $provider->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveProviderRequest;
    }
}
