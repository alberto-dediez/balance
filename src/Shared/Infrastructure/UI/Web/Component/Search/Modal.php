<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Search;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Search\GlobalSearchService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Search:Modal', template: '@components/Search/Modal.html.twig')]
final readonly class Modal
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
