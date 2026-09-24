<?php

declare(strict_types=1);

namespace App\Security\Application\User\RequestTwoFactorEnrolment;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\TotpAuthenticator;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RequestTwoFactorEnrolmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UserRepository $repository,
        private UserAuthorizer $authorizer,
        private TotpAuthenticator $totp,
        private string $issuer = '',
    ) {}

    public function __invoke(RequestTwoFactorEnrolmentRequest $request, User $performBy): RequestTwoFactorEnrolmentResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Edit, $performBy);

        if (TwoFactorMethod::Email === $request->method) {
            $user->enableTwoFactorByEmail();

            $this->repository->add($user);

            return new RequestTwoFactorEnrolmentResponse(
                method: TwoFactorMethod::Email,
            );
        }

        $secret = $this->totp->generateSecret();

        $user->requestTotpEnrolment($secret);

        $this->repository->add($user);

        return new RequestTwoFactorEnrolmentResponse(
            method: TwoFactorMethod::Totp,
            provisioningUri: $this->totp->provisioningUri($secret, $user->username(), $this->issuer),
            manualKey: $this->totp->manualKey($secret),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RequestTwoFactorEnrolmentRequest;
    }
}
