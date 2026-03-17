<?php

namespace App\Security\Domain\Model\User;

enum Role: string
{
    case User = 'ROLE_USER';

    case Owner = 'ROLE_OWNER';

    case Admin = 'ROLE_ADMIN';

    case SuperAdmin = 'ROLE_SUPER_ADMIN';

    /**
     * @return Role[]
     */
    public static function casesForAdmins(): array
    {
        $result = [];

        foreach (self::cases() as $role) {
            if ($role == self::SuperAdmin) {
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
            if ($role == self::Admin) {
                continue;
            }
            if ($role == self::SuperAdmin) {
                continue;
            }
            $result[$role->value] = $role;
        }

        return $result;
    }
}
