<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Revoke;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RevokeApiTokenService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ApiTokenRepository $repository,
        private ApiTokenAuthorizer $authorizer,
    ) {}

    public function __invoke(RevokeApiTokenRequest $request, User $user): RevokeApiTokenResponse
    {
        $apiToken = $this->repository->find($request->apiTokenId);

        if (null === $apiToken) {
            throw new \InvalidArgumentException('ApiToken not found');
        }

        $this->authorizer->denyUnlessCanDoThat($apiToken, Action::Remove, $user);

        $apiToken->revoke($user->id(), new \DateTimeImmutable('NOW'));

        $this->repository->add($apiToken);

        return new RevokeApiTokenResponse(
            apiTokenId: $apiToken->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RevokeApiTokenRequest;
    }
}
