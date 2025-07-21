<?php

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FakeSecurity implements AuthorizationCheckerInterface
{
    public function __construct(private SecurityUser $user)
    {

    }

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        if (is_null($subject)) {
            return $this->checkByRole($this->user, $attribute);
        }

        return false;
    }

    private function checkByRole(SecurityUser $user, $attribute)
    {
        return in_array($attribute, $user->getRoles());
    }

    public static function fromRole(Role $role)
    {
        $user = UserMother::fromUsernameAndRole('admin', $role);
        
        $securityUser = new SecurityUser($user);

        return new FakeSecurity($securityUser);
    }
}