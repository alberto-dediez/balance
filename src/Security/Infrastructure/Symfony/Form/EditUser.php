<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;

class EditUser
{
    /**
     * @param Role[] $roles
     */
    public function __construct(public array $roles) {}
}
