<?php

namespace App\Security\Infrastructure\Symfony\Security;

use Symfony\Bundle\SecurityBundle\Security;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRepository;

class LoggedAccount
{
    private ?Account $account = null;

    public function __construct(Security $security, AccountRepository $accountRepository)
    {
        $user = $security->getUser();

        if (null == $user || null == $user->accountId()) {
            return ;
        }

        $this->account = $accountRepository->find($user->accountId());
    }

    public function __invoke()
    {
        return $this->account;   
    }
}