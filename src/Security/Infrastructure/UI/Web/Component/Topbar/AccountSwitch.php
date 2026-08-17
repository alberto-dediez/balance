<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Component\Topbar;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Topbar:AccountSwitch', template: '@components/Topbar/AccountSwitch.html.twig')]
final readonly class AccountSwitch
{
    public function __construct(
        private ImpersonatedAccount $impersonatedAccount,
        private LoggedUser $loggedUser,
        private AccountAuthorizer $authorizer,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private AccountRepository $accountRepository,
        private bool $accountImpersonationEnabled,
    ) {}

    public function enabled(): bool
    {
        if (!$this->accountImpersonationEnabled) {
            return false;
        }

        if ($this->security->isGranted('IS_IMPERSONATOR')) {
            return false;
        }

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return false;
        }

        return $this->authorizer->canImpersonateAccounts($user);
    }

    public function csrfToken(): string
    {
        return $this->csrfTokenManager->getToken('account_impersonate')->getValue();
    }

    public function impersonatedAccount(): ?Account
    {
        return $this->impersonatedAccount->account();
    }

    /**
     * @return array<int, Account>
     */
    public function accounts(): array
    {
        return $this->accountRepository->findByFilter(new AccountFilter(active: true, limit: 0));
    }
}
