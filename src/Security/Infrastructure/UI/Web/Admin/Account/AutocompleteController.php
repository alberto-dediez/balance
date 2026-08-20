<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\Autocomplete\AutocompleteAccountRequest;
use App\Security\Application\Account\Autocomplete\AutocompleteAccountService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/accounts/autocomplete', name: 'admin_accounts_autocomplete')]
class AutocompleteController extends AbstractController
{
    public function __construct(private readonly bool $accountImpersonationEnabled) {}

    public function __invoke(
        #[MapQueryParameter]
        string $query,
        AutocompleteAccountService $autocompleteAccount,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->accountImpersonationEnabled) {
            throw new NotFoundHttpException();
        }

        $response = $autocompleteAccount(
            new AutocompleteAccountRequest(
                query: $query,
            ),
            $loggedUser(),
        );

        return $this->json(
            $response->elements,
        );
    }
}
