<?php

namespace App\Security\Infrastructure\Symfony\Security;

use Symfony\Bundle\SecurityBundle\Security;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRepository;

class LoggedAccount
{
    private ?Account $account = null;

    private bool $loaded = false;

    public function __construct(private Security $security, private AccountRepository $accountRepository)
    {

    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser || null === $user->accountId()) {
            return ;
        }

        $this->account = $this->accountRepository->find($user->accountId());
        $this->loaded = true;
    }

    public function __invoke()
    {
        $this->load();

        return $this->account;
    }
}
