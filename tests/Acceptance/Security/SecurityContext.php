<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Security;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;

use function PHPUnit\Framework\assertNotEmpty;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class SecurityContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
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
    public function userWithRoleExists(string $username, string $role): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::fromUsernameAndRole($username, Role::tryFrom($role));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id exists')]
    public function userWithIdExists(string $username, string $id): void
    {
        $user = UserMother::fromUsernameAndRoleAndId($username, Role::User, new UserId($id));

        $this->getUserRepository()->add($user);
    }

    #[Given('account :name with id :id exists')]
    public function accountWithIdExists(string $username, string $id): void
    {
        $account = AccountMother::fromNameAndId($username, $id);

        $this->getAccountRepository()->add($account);
    }

    #[Given('user :username with role :role of account :account exists')]
    public function userWithRoleOfAccountExists(string $username, string $role, string $account): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::fromUsernameAndRoleAndAccount($username, Role::tryFrom($role), new AccountId($account));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id of account :account exists')]
    public function userWithIdOfAccountExists(string $username, string $id, string $account): void
    {
        $user = UserMother::from(
            userId: new UserId($id),
            username: $username,
            role: Role::User,
            accountId: new AccountId($account),
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with role :role with id :id of account :account exists')]
    public function userWithRoleWithIdOfAccountExists(string $username, string $id, string $role, string $account): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::from(
            userId: new UserId($id),
            username: $username,
            role: Role::tryFrom($role),
            accountId: new AccountId($account),
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with recover token :token exists')]
    public function userWithRecoverTokenExists(string $username, string $token): void
    {
        $user = UserMother::fromUsernameAndRecoverToken($username, $token);

        $this->getUserRepository()->add($user);
    }
}
