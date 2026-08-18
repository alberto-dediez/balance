<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/accounts/impersonate/exit', name: 'admin_accounts_impersonate_exit', methods: ['POST'])]
class ExitImpersonationController extends AbstractController
{
    public function __construct(
        private readonly bool $accountImpersonationEnabled,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(
        Request $request,
        ImpersonatedAccount $impersonatedAccount,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->accountImpersonationEnabled) {
            throw new NotFoundHttpException();
        }

        $token = (string) $request->request->get('_token', '');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('account_impersonate', $token))) {
            return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        $account = $impersonatedAccount->account();

        $impersonatedAccount->exit();

        $user = $loggedUser();

        $this->logger->info('account_impersonation.exit', [
            'impersonator' => (string) $user->id(),
            'account' => $account instanceof Account ? (string) $account->id() : null,
            'action' => 'exit',
        ]);

        return new JsonResponse(['ok' => true, 'redirect' => $this->generateUrl('home')], Response::HTTP_OK);
    }
}
