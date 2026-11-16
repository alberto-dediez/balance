<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.shortcut.provider')]
interface ShortcutProvider
{
    public function isAvailableTo(User $user): bool;

    /** @return list<Shortcut> */
    public function shortcuts(User $user): array;
}
