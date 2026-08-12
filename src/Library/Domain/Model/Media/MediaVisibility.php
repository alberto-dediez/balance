<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

enum MediaVisibility: string
{
    /** Only the users of the account owning the file. */
    case Account = 'Account';

    /** Every user logged in the platform, whatever its account. */
    case System = 'System';

    /** Anyone, even without being logged in the platform. */
    case Public = 'Public';

    /**
     * The access to the file escapes the account it belongs to. It says nothing about
     * the file being browsable from the library of the other accounts, which is the
     * separate decision Media::isShared() carries.
     */
    public function isAccessibleOutsideAccount(): bool
    {
        return self::Account !== $this;
    }

    public function isPublic(): bool
    {
        return self::Public === $this;
    }

    /**
     * The visibilities reaching at least the given audience: a field requiring shared
     * files accepts the wider ones too.
     *
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
