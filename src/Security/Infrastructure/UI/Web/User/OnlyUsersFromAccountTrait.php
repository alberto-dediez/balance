<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Translation\TranslatableMessage;

trait OnlyUsersFromAccountTrait
{
    protected function onlyUsersFromAccount(?User $user, ?Account $account)
    {
        if (null == $user || null == $account || null == $user->accountId() || false == $account->id()->equals($user->accountId())) {
            throw $this->createNotFoundException(new TranslatableMessage("El usuario no existe"));
        }
    }
}
