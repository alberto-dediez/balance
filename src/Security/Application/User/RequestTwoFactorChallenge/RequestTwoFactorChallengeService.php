<?php

declare(strict_types=1);

namespace App\Security\Application\User\RequestTwoFactorChallenge;

use App\Security\Application\Action;
use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Application\User\TwoFactor\MaskedDestination;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\TwoFactorChallenge;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RequestTwoFactorChallengeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UserRepository $repository,
        private UserAuthorizer $authorizer,
        private EffectiveTwoFactorMethod $effectiveMethod,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(RequestTwoFactorChallengeRequest $request, User $performBy): RequestTwoFactorChallengeResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Edit, $performBy);

        $method = $this->effectiveMethod->resolve($user);

        if (!$method instanceof TwoFactorMethod) {
            throw new TwoFactorNotPendingException();
        }

        $maskedDestination = TwoFactorMethod::Email === $method ? MaskedDestination::email($user->username()) : '';

        if (!$user->twoFactorChallenge()->isPending() || ($request->force && TwoFactorMethod::Email === $method)) {
            $challenge = TwoFactorMethod::Email === $method
                ? TwoFactorChallenge::generate($this->appSecret)
                : TwoFactorChallenge::withoutCode();

            $user->requestTwoFactorChallenge($method, $challenge, $maskedDestination);

            $this->repository->add($user);
        }

        return new RequestTwoFactorChallengeResponse(
            method: $method,
            maskedDestination: $maskedDestination,
            isFallback: $this->effectiveMethod->isFallback($user),
            expiresAt: $user->twoFactorChallenge()->expiresAt(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RequestTwoFactorChallengeRequest;
    }
}
