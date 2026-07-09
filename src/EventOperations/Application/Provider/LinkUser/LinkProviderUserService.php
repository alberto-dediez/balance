<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\LinkUser;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LinkProviderUserService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProviderRepository $providerRepository,
        private ProviderAuthorizer $authorizer,
    ) {}

    public function __invoke(LinkProviderUserRequest $request, User $user): LinkProviderUserResponse
    {
        $provider = $this->providerRepository->find($request->providerId);

        if (null == $provider) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $this->authorizer->denyUnlessCanDoThat($provider, SecurityAction::Edit, $user);

        $provider->linkUser($request->userId);

        $this->providerRepository->add($provider);

        return new LinkProviderUserResponse(
            providerId: $provider->id(),
            userId: $request->userId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LinkProviderUserRequest;
    }
}
