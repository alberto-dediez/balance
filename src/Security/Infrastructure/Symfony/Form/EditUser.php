<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Model\Locale\Locale;

class EditUser
{
    /**
     * @param Role[] $roles
     */
    public function __construct(public array $roles, public ?Locale $preferredLocale = null) {}
}
