<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Shortcut;

use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Shortcut\KeyCombination;
use App\Shared\Application\Shortcut\Shortcut;
use App\Shared\Application\Shortcut\ShortcutProvider;

class AssistantShortcutProvider implements ShortcutProvider
{
    public const string TOGGLE_EVENT = 'assistant-toggle';

    public function __construct(private readonly AssistantAccess $access) {}

    public function isAvailableTo(User $user): bool
    {
        return $this->access->isGranted();
    }

    public function shortcuts(User $user): array
    {
        if (!$this->isAvailableTo($user)) {
            return [];
        }

        return [
            Shortcut::dispatching(KeyCombination::key('a'), 'Asistente IA', 'solar:chat-round-dots-line-duotone', 500, self::TOGGLE_EVENT),
        ];
    }
}
