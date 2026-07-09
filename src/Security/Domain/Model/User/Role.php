<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

enum Role: string
{
    case User = 'ROLE_USER';

    case Owner = 'ROLE_OWNER';

    case Admin = 'ROLE_ADMIN';

    case SuperAdmin = 'ROLE_SUPER_ADMIN';

    case Provider = 'ROLE_PROVIDER';

    /**
     * @return Role[]
     */
    public static function casesForAdmins(): array
    {
        $result = [];

        foreach (self::cases() as $role) {
            if (self::SuperAdmin == $role) {
                continue;
            }

            $result[$role->value] = $role;
        }

        return $result;
    }

    /**
     * @return Role[]
     */
    public static function casesForOwners(): array
    {
        $result = [];

        foreach (self::cases() as $role) {
            if (self::Admin == $role) {
                continue;
            }
            if (self::SuperAdmin == $role) {
                continue;
            }
            if (self::Provider == $role) {
                continue;
            }
            $result[$role->value] = $role;
        }

        return $result;
    }
}
