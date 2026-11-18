<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Shortcut;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Shortcut\Shortcut;
use App\Shared\Application\Shortcut\ShortcutProvider;

class FakeShortcutProvider implements ShortcutProvider
{
    public int $calls = 0;

    /** @param list<Shortcut> $shortcuts */
    public function __construct(
        private readonly bool $available = true,
        private readonly array $shortcuts = [],
    ) {}

    public static function withShortcuts(bool $available, Shortcut ...$shortcuts): self
    {
        return new self($available, array_values($shortcuts));
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->available;
    }

    public function shortcuts(User $user): array
    {
        ++$this->calls;

        return $this->available ? $this->shortcuts : [];
    }
}
