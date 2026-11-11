<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Register;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenLimitReachedException;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\ApiToken\ApiTokensNotAllowedException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterApiTokenService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ApiTokenRepository $repository,
        private ApiTokenAuthorizer $authorizer,
        private ApiTokenPolicy $policy,
    ) {}

    public function __invoke(RegisterApiTokenRequest $request, User $user): RegisterApiTokenResponse
    {
        $id = ApiTokenId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new ApiToken(id: $id, userId: $user->id(), accountId: $user->accountId()),
            Action::Register,
            $user,
        );

        if (!$this->policy->userCanHaveTokens($user)) {
            throw new ApiTokensNotAllowedException();
        }

        $now = new \DateTimeImmutable('NOW');

        if ($this->repository->countActiveByUserId($user->id(), $now) >= ApiToken::MAX_ACTIVE_PER_USER) {
            throw new ApiTokenLimitReachedException();
        }

        $expiresAt = $request->expiresAt?->setTime(23, 59, 59);

        $secret = ApiTokenSecret::generate();

        $apiToken = new ApiToken($id);
        $apiToken->register(
            userId: $user->id(),
            accountId: $user->accountId(),
            name: $request->name,
            secret: $secret,
            expiresAt: $expiresAt,
            now: $now,
        );

        $this->repository->add($apiToken);

        return new RegisterApiTokenResponse(
            apiTokenId: $apiToken->id(),
            name: $apiToken->name(),
            plainValue: $secret->plainValue(),
            expiresAt: $apiToken->expiresAt(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterApiTokenRequest;
    }
}
