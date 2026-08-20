<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/accounts/impersonate', name: 'admin_accounts_impersonate', methods: ['POST'])]
class ImpersonateController extends AbstractController
{
    public function __construct(
        private readonly bool $accountImpersonationEnabled,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(
        Request $request,
        AccountAuthorizer $authorizer,
        AccountRepository $accountRepository,
        ImpersonatedAccount $impersonatedAccount,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->accountImpersonationEnabled) {
            throw new NotFoundHttpException();
        }

        $user = $loggedUser();

        if (!$authorizer->canImpersonateAccounts($user)) {
            throw new AccessDeniedException();
        }

        $token = (string) $request->request->get('_token', '');
        $rawAccountId = $request->request->get('accountId');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('account_impersonate', $token))) {
            return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($rawAccountId) || '' === $rawAccountId) {
            return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $accountId = new AccountId($rawAccountId);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        $account = $accountRepository->find($accountId);

        if (null === $account) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $authorizer->denyUnlessCanDoThat($account, Action::Impersonate, $user);
        } catch (UnauthorizedActionException) {
            throw new AccessDeniedException();
        }

        $impersonatedAccount->impersonate($account->id(), $user->id());

        $this->logger->info('account_impersonation.enter', [
            'impersonator' => (string) $user->id(),
            'account' => (string) $account->id(),
            'action' => 'enter',
        ]);

        return new JsonResponse(['ok' => true, 'redirect' => $this->generateUrl('home')], Response::HTTP_OK);
    }
}
