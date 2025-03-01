<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;

final class SecurityContext implements Context
{
    public function __construct(
        private UserRepository $userRepository, 
        private AccountRepository $accountRepository
    ) {
    }

    #[Given('user :username with role :role exists')]
    public function userWithRoleExists($username, $role): void
    {
        $user = UserMother::fromUsernameAndRole($username, Role::tryFrom($role));
        
        $this->userRepository->add($user);      
    }

    #[Given('user :username with id :id exists')]
    public function userWithIdExists($username, $id): void
    {
        $user = UserMother::fromUsernameAndRoleAndId($username, Role::User, new UserId($id));
        
        $this->userRepository->add($user);      
    }

    #[Given('account :name with id :id exists')]
    public function accountWithIdExists($username, $id): void
    {
        $account = AccountMother::fromNameAndId($username, $id);
        
        $this->accountRepository->add($account);      
    }    
}
