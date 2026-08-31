<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ResetInterface;

class ImpersonatedAccount implements ResetInterface
{
    private const string SESSION_KEY = '_impersonated_account_id';

    private const string IMPERSONATOR_KEY = '_impersonated_by';

    private ?Account $account = null;

    private bool $loaded = false;

    private ?UserId $forcedImpersonatorId = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AccountRepository $accountRepository,
        private readonly bool $accountImpersonationEnabled,
    ) {}

    public function accountId(): ?AccountId
    {
        return $this->account()?->id();
    }

    public function account(): ?Account
    {
        if (!$this->accountImpersonationEnabled) {
            return null;
        }

        if ($this->loaded) {
            return $this->account;
        }

        $this->loaded = true;

        $accountId = $this->readSession();

        if (!$accountId instanceof AccountId) {
            return null;
        }

        $this->account = $this->accountRepository->find($accountId);

        if (!$this->account instanceof Account) {
            $this->clearSession();
        }

        return $this->account;
    }

    public function isImpersonating(): bool
    {
        return $this->account() instanceof Account;
    }

    public function impersonatorId(): ?UserId
    {
        if (!$this->accountImpersonationEnabled) {
            return null;
        }

        if ($this->forcedImpersonatorId instanceof UserId) {
            return $this->forcedImpersonatorId;
        }

        $session = $this->getSession();

        if (null === $session || !$session->has(self::IMPERSONATOR_KEY)) {
            return null;
        }

        $value = $session->get(self::IMPERSONATOR_KEY);

        return is_string($value) ? new UserId($value) : null;
    }

    public function impersonate(AccountId $accountId, UserId $impersonatorId): void
    {
        if (!$this->accountImpersonationEnabled) {
            return;
        }

        $this->writeSession($accountId, $impersonatorId);

        $this->account = $this->accountRepository->find($accountId);
        $this->loaded = true;
    }

    public function force(?AccountId $accountId, ?UserId $impersonatorId): void
    {
        if (!$this->accountImpersonationEnabled) {
            return;
        }

        $this->forcedImpersonatorId = $impersonatorId;

        if (!$accountId instanceof AccountId) {
            $this->account = null;
            $this->loaded = false;

            return;
        }

        $this->account = $this->accountRepository->find($accountId);
        $this->loaded = true;
    }

    public function exit(): void
    {
        $this->clearSession();

        $this->account = null;
        $this->loaded = true;
    }

    public function reset(): void
    {
        $this->account = null;
        $this->loaded = false;
        $this->forcedImpersonatorId = null;
    }

    private function readSession(): ?AccountId
    {
        if (!$this->accountImpersonationEnabled) {
            return null;
        }

        $session = $this->getSession();

        if (null === $session || !$session->has(self::SESSION_KEY)) {
            return null;
        }

        $value = $session->get(self::SESSION_KEY);

        return is_string($value) ? new AccountId($value) : null;
    }

    private function writeSession(AccountId $accountId, UserId $impersonatorId): void
    {
        if (!$this->accountImpersonationEnabled) {
            return;
        }

        $session = $this->getSession();

        $session?->set(self::SESSION_KEY, (string) $accountId);
        $session?->set(self::IMPERSONATOR_KEY, (string) $impersonatorId);
    }

    private function clearSession(): void
    {
        $session = $this->getSession();

        $session?->remove(self::SESSION_KEY);
        $session?->remove(self::IMPERSONATOR_KEY);
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || $request->attributes->getBoolean('_stateless')) {
            return null;
        }

        return $request->hasSession() ? $request->getSession() : null;
    }
}
