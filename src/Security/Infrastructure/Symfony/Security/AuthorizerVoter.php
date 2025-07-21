<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Action;
use App\Security\Application\Authorizer;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthorizerVoter extends Voter
{
    public function __construct(
        private LoggedUser $loggedUser, 
        private AuthorizerFactory $authorizerFactory
    ) {
        
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        $action = Action::tryFrom($attribute);

        if (null == $action) {
            return false; 
        }

        $authorizer = $this->authorizerFactory->createFor($subject);

        if (null == $authorizer) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $authorizer = $this->authorizerFactory->createFor($subject);
        
        $action = Action::tryFrom($attribute);

        $user = $this->loggedUser->__invoke();

        return $authorizer->canDoThat($subject, $action, $user);
    }
}