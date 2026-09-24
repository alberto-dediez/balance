<?php

declare(strict_types=1);

namespace App\Security\Application\User\ConfirmTwoFactorEnrolment;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\TotpAuthenticator;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ConfirmTwoFactorEnrolmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UserRepository $repository,
        private UserAuthorizer $authorizer,
        private TotpAuthenticator $totp,
    ) {}

    public function __invoke(ConfirmTwoFactorEnrolmentRequest $request, User $performBy): ConfirmTwoFactorEnrolmentResponse
    {
        $user = $this->repository->find($request->userId);

        if (null == $user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat($user, Action::Edit, $performBy);

        try {
            $user->confirmTotpEnrolment($request->code, $this->totp);
        } catch (TwoFactorCodeInvalidException $e) {
            $this->repository->add($user);

            throw $e;
        }

        $this->repository->add($user);

        return new ConfirmTwoFactorEnrolmentResponse(
            method: TwoFactorMethod::Totp,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ConfirmTwoFactorEnrolmentRequest;
    }
}
