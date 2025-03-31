<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Action;
use App\Security\Application\Authorizer;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthorizerVoter extends Voter
{
    public function __construct(
        private LoggedUser $loggedUser, 
        #[AutowireIterator('application.authorizer')]
        private iterable $authorizers
    ) {
        
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        $action = Action::tryFrom($attribute);

        if (null == $action) {
            return false; 
        }

        $authorizer = $this->getAuthorizer($subject);

        if (null == $authorizer) {
            return false;
        }

        return true;
    }

    protected function getAuthorizer(mixed $subject): ?Authorizer
    {
        if (count($this->authorizers) == 0) {
            return null;
        }

        foreach ($this->authorizers as $authorizer) {
            if ($authorizer->supports($subject)) {
                return $authorizer;
            }
        }

        return null;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $authorizer = $this->getAuthorizer($subject);
        
        $action = Action::tryFrom($attribute);

        $user = $this->loggedUser->__invoke();

        return $authorizer->canDoThat($subject, $action, $user);
    }
}