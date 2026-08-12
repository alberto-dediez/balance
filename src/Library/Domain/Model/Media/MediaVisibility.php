<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

enum MediaVisibility: string
{
    case Account = 'Account';

    case System = 'System';

    case Public = 'Public';

    public function isShared(): bool
    {
        return self::Account !== $this;
    }

    public function isPublic(): bool
    {
        return self::Public === $this;
    }

    /**
     * @return list<self>
     */
    public static function sharedCases(): array
    {
        return [self::System, self::Public];
    }

    /**
     * @return list<self>
     */
    public static function casesSatisfying(self $required): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn(self $visibility): bool => $visibility->satisfies($required),
        ));
    }

    public function level(): int
    {
        return match ($this) {
            self::Account => 0,
            self::System => 1,
            self::Public => 2,
        };
    }

    public function satisfies(self $required): bool
    {
        return $this->level() >= $required->level();
    }
}
