<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Topbar;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Search\GlobalSearchService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Topbar:Search', template: '@components/Topbar/Search.html.twig')]
final readonly class Search
{
    public function __construct(
        private LoggedUser $loggedUser,
        private GlobalSearchService $globalSearch,
    ) {}

    public function enabled(): bool
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return false;
        }

        return $this->globalSearch->isAvailableTo($user);
    }
}
