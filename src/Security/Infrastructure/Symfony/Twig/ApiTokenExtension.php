<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Twig;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApiTokenExtension extends AbstractExtension
{
    public function __construct(
        private readonly LoggedUser $loggedUser,
        private readonly ApiTokenPolicy $policy,
    ) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_have_api_tokens', $this->canHaveApiTokens(...)),
        ];
    }

    public function canHaveApiTokens(): bool
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (null === $user) {
            return false;
        }

        return $this->policy->userCanHaveTokens($user);
    }
}
