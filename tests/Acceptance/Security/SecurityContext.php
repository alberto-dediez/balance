<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Security;

use Behat\Step\Given;
use Behat\Hook\AfterScenario;
use Behat\Behat\Context\Context;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;

final class SecurityContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope)
    {
        $this->client->reset();
    }

    private function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    private function getAccountRepository(): AccountRepository
    {
        return $this->client->getContainer()->get(AccountRepository::class);
    }

    #[Given('user :username with role :role exists')]
    public function userWithRoleExists($username, $role): void
    {
        $user = UserMother::fromUsernameAndRole($username, Role::tryFrom($role));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id exists')]
    public function userWithIdExists($username, $id): void
    {
        $user = UserMother::fromUsernameAndRoleAndId($username, Role::User, new UserId($id));

        $this->getUserRepository()->add($user);
    }

    #[Given('account :name with id :id exists')]
    public function accountWithIdExists($username, $id): void
    {
        $account = AccountMother::fromNameAndId($username, $id);

        $this->getAccountRepository()->add($account);
    }

    #[Given('user :username with role :role of account :account exists')]
    public function userWithRoleOfAccountExists($username, $role, $account): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount($username, Role::tryFrom($role), new AccountId($account));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id of account :account exists')]
    public function userWithIdOfAccountExists($username, $id, $account): void
    {
        $user = UserMother::from(
            username: $username,
            role: Role::User,
            userId: new UserId($id),
            accountId: new AccountId($account)
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with role :role with id :id of account :account exists')]
    public function userWithRoleWithIdOfAccountExists($username, $id, $role, $account): void
    {
        $user = UserMother::from(
            username: $username,
            role: Role::tryFrom($role),
            userId: new UserId($id),
            accountId: new AccountId($account)
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with recover token :token exists')]
    public function userWithRecoverTokenExists($username, $token): void
    {
        $user = UserMother::fromUsernameAndRecoverToken($username, $token);

        $this->getUserRepository()->add($user);
    }
}
