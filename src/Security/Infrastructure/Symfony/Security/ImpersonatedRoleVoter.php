<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
class ImpersonatedRoleVoter extends Voter
{
    public function __construct(private readonly CurrentRolesProvider $currentRolesProvider) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!Role::tryFrom($attribute) instanceof Role) {
            return false;
        }

        return null !== $this->currentRolesProvider->currentLoggedUserRoles();
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentRoles = $this->currentRolesProvider->currentLoggedUserRoles();

        if (null === $currentRoles) {
            return false;
        }

        return in_array(Role::from($attribute), $currentRoles, true);
    }
}
