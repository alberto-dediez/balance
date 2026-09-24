<?php

declare(strict_types=1);

namespace App\Security\Application\User\VerifyTwoFactorChallenge;

use App\Security\Application\Action;
use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\TotpAuthenticator;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class VerifyTwoFactorChallengeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UserRepository $repository,
        private UserAuthorizer $authorizer,
        private EffectiveTwoFactorMethod $effectiveMethod,
        private TotpAuthenticator $totp,
        #[\SensitiveParameter]
        private string $appSecret = '',
    ) {}

    public function __invoke(VerifyTwoFactorChallengeRequest $request, User $performBy): VerifyTwoFactorChallengeResponse
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

        try {
            $user->verifyTwoFactor($request->code, $method, $this->appSecret, $this->totp);
        } catch (TwoFactorCodeInvalidException $e) {
            $this->repository->add($user);

            throw $e;
        }

        $this->repository->add($user);

        return new VerifyTwoFactorChallengeResponse(
            method: $method,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof VerifyTwoFactorChallengeRequest;
    }
}
