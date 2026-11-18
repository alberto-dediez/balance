<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ShortcutLister
{
    /** @param iterable<ShortcutProvider> $providers */
    public function __construct(
        #[AutowireIterator('shared.shortcut.provider')]
        private readonly iterable $providers,
    ) {}

    /** @return list<Shortcut> */
    public function shortcutsFor(User $user): array
    {
        $shortcuts = [];

        foreach ($this->providers as $provider) {
            if (!$provider->isAvailableTo($user)) {
                continue;
            }

            foreach ($provider->shortcuts($user) as $shortcut) {
                $id = $shortcut->combination->id();

                if (isset($shortcuts[$id])) {
                    throw new DuplicatedShortcutException($shortcut->combination, $shortcuts[$id]->label, $shortcut->label);
                }

                $shortcuts[$id] = $shortcut;
            }
        }

        usort($shortcuts, fn(Shortcut $a, Shortcut $b): int => $a->position <=> $b->position ?: strcmp($a->label, $b->label));

        return $shortcuts;
    }
}
