<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Search\GlobalSearchService;

class PlatformShortcutProvider implements ShortcutProvider
{
    public const string SEARCH_EVENT = 'global-search';
    public const string HELP_EVENT = 'help';

    public function __construct(private readonly GlobalSearchService $globalSearch) {}

    public function isAvailableTo(User $user): bool
    {
        return true;
    }

    public function shortcuts(User $user): array
    {
        $shortcuts = [];

        if ($this->globalSearch->isAvailableTo($user)) {
            $shortcuts[] = Shortcut::dispatching(KeyCombination::ctrl('k'), 'Buscar', 'solar:magnifer-line-duotone', 0, self::SEARCH_EVENT);
        }

        $shortcuts[] = Shortcut::submitting(KeyCombination::ctrl('enter'), 'Enviar formulario', 'solar:plain-line-duotone', 10);
        $shortcuts[] = Shortcut::dispatching(KeyCombination::ctrl('f1'), 'Atajos de teclado', 'solar:keyboard-line-duotone', 1000, self::HELP_EVENT);

        return $shortcuts;
    }
}
