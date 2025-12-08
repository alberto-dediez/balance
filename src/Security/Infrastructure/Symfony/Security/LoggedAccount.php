<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRepository;

class LoggedAccount
{
    private array $accounts = [];
    public function __construct(private LoggedUser $loggedUser, private AccountRepository $accountRepository)
    {

    }

    public function __invoke(): ?Account
    {
        $user = $this->loggedUser->__invoke();

        if (null === $user || null === $user->accountId()) {
            return null;
        }

        if (!isset($this->accounts[(string) $user->id()])) {
            $this->accounts[(string) $user->id()] = $this->accountRepository->find($user->accountId());
        }

        return $this->accounts[(string) $user->id()];
    }
}
