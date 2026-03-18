<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

trait OnlyUsersFromAccountTrait
{
    protected function onlyUsersFromAccount(?User $user, ?Account $account)
    {
        if (in_array(null, [$user, $account, $user->accountId()]) || false === $account->id()->equals($user->accountId())) {
            throw $this->createNotFoundException(new TranslatableMessage('El usuario no existe'));
        }
    }
}
