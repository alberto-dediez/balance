<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Action;
use App\Security\Application\Authorizer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
class AuthorizerVoter extends Voter
{
    public function __construct(
        private readonly LoggedUser $loggedUser,
        private readonly AuthorizerFactory $authorizerFactory,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $action = Action::tryFrom($attribute);

        if (null == $action) {
            return false;
        }

        $authorizer = $this->authorizerFactory->createFor($subject);

        return null != $authorizer;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $authorizer = $this->authorizerFactory->createFor($subject);

        if (!$authorizer instanceof Authorizer) {
            return false;
        }

        $action = Action::tryFrom($attribute);

        if (null === $action) {
            return false;
        }

        $user = $this->loggedUser->__invoke();

        return $authorizer->canDoThat($subject, $action, $user);
    }
}
