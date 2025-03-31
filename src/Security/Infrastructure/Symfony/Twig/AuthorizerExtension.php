<?php

namespace App\Security\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use App\Security\Application\Action;
use Twig\Extension\AbstractExtension;
use App\Security\Infrastructure\Symfony\Security\AuthorizerFactory;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Doctrine\Migrations\Query\Exception\InvalidArguments;

class AuthorizerExtension extends AbstractExtension
{
    public function __construct(
        private AuthorizerFactory $authorizerFactory, 
        private LoggedUser $loggedUser
    ) {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('is_authorized', [$this, 'isAuthorized']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function isAuthorized(mixed $subject, Action|string $action)
    {
        $action = is_string($action) ? Action::tryFrom($action) : $action;

        $authorizer = $this->authorizerFactory->createFor($subject);

        if (null == $action || null == $authorizer) {
            throw new InvalidArguments("Invalid subject or action");
        }

        $user = $this->loggedUser->__invoke();

        return $authorizer->canDoThat($subject, $action, $user);
    }
}
