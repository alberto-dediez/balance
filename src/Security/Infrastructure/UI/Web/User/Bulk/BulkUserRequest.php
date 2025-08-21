<?php

namespace App\Security\Infrastructure\UI\Web\User\Bulk;

use App\Security\Domain\Model\User\Role;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;

final class BulkUserRequest extends BulkRequest
{
    public Role $role = Role::User;
}