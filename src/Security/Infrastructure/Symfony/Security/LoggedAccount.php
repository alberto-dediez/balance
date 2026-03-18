<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Bundle\SecurityBundle\Security;

class LoggedAccount
{
    private ?Account $account = null;

    private bool $loaded = false;

    public function __construct(private readonly Security $security, private readonly AccountRepository $accountRepository) {}

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser || !$user->accountId() instanceof AccountId) {
            return;
        }

        $this->account = $this->accountRepository->find($user->accountId());
        $this->loaded = true;
    }

    public function __invoke(): ?Account
    {
        $this->load();

        return $this->account;
    }
}
