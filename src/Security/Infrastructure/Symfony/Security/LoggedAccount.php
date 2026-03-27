<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

class LoggedAccount
{
    /** @var Account[] */
    private array $accounts = [];

    public function __construct(private readonly LoggedUser $loggedUser, private readonly AccountRepository $accountRepository) {}

    public function __invoke(): ?Account
    {
        $user = $this->loggedUser->__invoke();

        if (!$user->accountId() instanceof AccountId) {
            return null;
        }

        $key = $user->id()->__toString();

        if (!isset($this->accounts[$key])) {
            $account = $this->accountRepository->find($user->accountId());
            if (!$account instanceof Account) {
                return null;
            }
            $this->accounts[$key] = $account;
        }

        return $this->accounts[$key];
    }
}
