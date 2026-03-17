<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Action;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @extends Voter<string, mixed>
 */
class AuthorizerVoter extends Voter
{
    public function __construct(
        private readonly LoggedUser $loggedUser,
        private readonly AuthorizerFactory $authorizerFactory
    ) {

    }

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

        $action = Action::tryFrom($attribute);

        $user = $this->loggedUser->__invoke();

        return $authorizer->canDoThat($subject, $action, $user);
    }
}
