<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Shortcuts;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Shortcut\Shortcut;
use App\Shared\Application\Shortcut\ShortcutLister;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Shortcuts:Modal', template: '@components/Shortcuts/Modal.html.twig')]
final readonly class Modal
{
    public function __construct(
        private LoggedUser $loggedUser,
        private ShortcutLister $lister,
    ) {}

    public function enabled(): bool
    {
        return $this->loggedUser->getLoggedUserOrNull() instanceof User;
    }

    /** @return list<Shortcut> */
    public function shortcuts(): array
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return [];
        }

        return $this->lister->shortcutsFor($user);
    }
}
